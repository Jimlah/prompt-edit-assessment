import CheckboxRoot from './Checkbox.vue';
import CheckboxIndicator from './CheckboxIndicator.vue';

export const Checkbox = Object.assign(CheckboxRoot, {
    Indicator: CheckboxIndicator,
});

export { checkboxVariants } from './checkboxVariants';
