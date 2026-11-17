import TextFieldRoot from './TextField.vue';
import TextFieldError from './TextFieldError.vue';
import TextFieldGroup from './TextFieldGroup.vue';
import TextFieldInput from './TextFieldInput.vue';
import TextFieldLabel from './TextFieldLabel.vue';

export const TextField = Object.assign(TextFieldRoot, {
    Label: TextFieldLabel,
    Group: TextFieldGroup,
    Input: TextFieldInput,
    Error: TextFieldError,
});

export const Textfield = TextField;

export { textFieldVariants } from './textFieldVariants';
