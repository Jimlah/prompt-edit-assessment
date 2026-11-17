import TextareaRoot from './Textarea.vue';
import TextareaError from './TextareaError.vue';
import TextareaGroup from './TextareaGroup.vue';
import TextareaInput from './TextareaInput.vue';
import TextareaLabel from './TextareaLabel.vue';

export const Textarea = Object.assign(TextareaRoot, {
    Label: TextareaLabel,
    Group: TextareaGroup,
    Input: TextareaInput,
    Error: TextareaError,
});

export { textareaVariants } from './textareaVariants';
