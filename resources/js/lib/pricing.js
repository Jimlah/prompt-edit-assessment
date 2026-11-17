/**
 * Cost estimation for the generate screen, mirroring the server-side logic in
 * App\Services\PixelforgePriceSheet. All amounts are integer nanodollars
 * (1 nd = $0.000000001); values stay far below Number.MAX_SAFE_INTEGER.
 */

/**
 * @param pricing  The `pricing` prop from the Dashboard page.
 * @param input    {prompt, size, quality, referenceImages}
 * @returns {{min_nd: number, max_nd: number}|null} The user's estimated cost, markup included.
 */
export function computeEstimateNd(pricing, { prompt, size, quality, referenceImages = [] }) {
    if (!pricing || !pricing.rates_nd) {
        return null;
    }

    const output = pricing.output?.[quality]?.[size];
    const reasoning = pricing.reasoning?.[quality];

    if (output === undefined || !reasoning) {
        return null;
    }

    const rates = pricing.rates_nd;
    const tokens = (count, rate) => Math.floor((count * rate) / 1e6);

    const chars = [...prompt].length;
    const textTokens = Math.ceil(chars / pricing.chars_per_text_token);
    const variance = pricing.text_token_variance;
    const textMin = Math.max(0, Math.ceil(textTokens * (1 - variance)));
    const textMax = textTokens + Math.ceil(textTokens * variance);

    const markup = (nanodollars) =>
        Math.floor((nanodollars * pricing.markup.numerator) / pricing.markup.denominator);

    const imageTokens = pricing.per_reference_image * referenceImages.length;

    const min =
        tokens(textMin, rates.input_text_tokens) +
        tokens(imageTokens, rates.input_image_tokens) +
        tokens(output, rates.output_tokens) +
        tokens(reasoning.min, rates.reasoning_tokens);
    const max =
        tokens(textMax, rates.input_text_tokens) +
        tokens(imageTokens, rates.input_image_tokens) +
        tokens(output, rates.output_tokens) +
        tokens(reasoning.max, rates.reasoning_tokens);

    return { min_nd: markup(min), max_nd: markup(max) };
}

/**
 * Format an integer-nanodollar amount for display: "$0.0473".
 * @param nd number|string|null
 */
export function formatNd(nd) {
    if (nd === null || nd === undefined || nd === '') {
        return null;
    }

    const value = Number(nd);

    if (Number.isNaN(value)) {
        return null;
    }

    const dollars = Math.round(value / 10 ** 5) / 10 ** 4;

    return '$' + dollars.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
}

/**
 * "$0.0473 – $0.0512", or a single amount when the two ends match.
 * @param min string|number|null
 * @param max string|number|null
 */
export function formatRangeNd(min, max) {
    const lower = formatNd(min);
    const upper = formatNd(max);

    if (lower === null || upper === null) {
        return null;
    }

    return lower === upper ? lower : `${lower} – ${upper}`;
}
