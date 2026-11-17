import PasswordRoot from './Password.vue';
import PasswordError from './PasswordError.vue';
import PasswordGroup from './PasswordGroup.vue';
import PasswordInput from './PasswordInput.vue';
import PasswordLabel from './PasswordLabel.vue';
import PasswordToggle from './PasswordToggle.vue';

export const Password = Object.assign(PasswordRoot, {
    Label: PasswordLabel,
    Group: PasswordGroup,
    Input: PasswordInput,
    Toggle: PasswordToggle,
    Error: PasswordError,
});

export { passwordVariants } from './passwordVariants';
