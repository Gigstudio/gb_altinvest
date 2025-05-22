// Auth.js (переработанный)
import { isNested, sendMessage, showSnack, translit, updateConsole } from './Utils.js';
import { APIClient } from './APIClient.js';

const STATUS = {
    SUCCESS: 'success',
    ERROR: 'error',
    AVAILABLE: 'available',
    EXISTS_LDAP: 'exists_ldap',
    EXISTS_LOCAL: 'exists_local',
    EXISTS_BOTH: 'exists_both'
};

function highlightField(field, message) {
    field.parentElement.classList.add('input-error', 'blink');
    field.parentElement.firstElementChild.title = message;
}

function resetErrors(form) {
    form.user?.parentElement.classList.remove('input-error', 'blink');
    form.user?.parentElement.firstElementChild.removeAttribute('title');
    form.password?.parentElement.classList.remove('input-error', 'blink');
    form.password?.parentElement.firstElementChild.removeAttribute('title');
}

function showSuccessAndClose(modal, message) {
    sendMessage(0, message || 'Успешная авторизация', 'Auth.js');
    showSnack('Добро пожаловать!');
    setTimeout(() => modal.remove(), 2000);
}

async function checkLoginExtended(login) {
    try {
        return await APIClient.send('auth', 'checkLoginExtended', { login });
    } catch (err) {
        sendMessage(3, err.message || 'Сбой обращения к серверу', 'Auth.js');
        showSnack(err.message || 'Сбой обращения к серверу');
        return null;
    }
}

export const Auth = {
    initLoginUI() {
        const modal = document.getElementById('modalbg');
        const closebtn = document.getElementById('close_login');
        const loginForm = document.forms.signin;
        // const regForm = document.forms.signup;
        const wrapper = document.querySelector('.reg-wrapper');
        const passFields = document.querySelectorAll('input[type="password"]');
        const passShowControls = document.querySelectorAll('label.showpass');

        const bageInput = document.getElementById('bage');
        // const regUserInput = document.getElementById('reguser');
        const loginCheckInput = document.getElementById('logincheck');
        const hintWarn = document.getElementById('hint-warn');
        const loginCheckHolder = hintWarn?.previousElementSibling;
        const nextBtn = document.getElementById('reg-next');
        const prevBtn = document.getElementById('reg-prev');
        const hintText = document.querySelector('.inputset.hint .hint-text');
        const hintCard = document.querySelector('.inputset.hint .card-wrapper');
        const hintLogin = document.querySelector('.inputset.hint .hint-info');

        let foundUser = null;

        // ===== Вход =====
        loginForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                login: loginForm.user.value.trim(),
                pass: loginForm.password.value
            };

            resetErrors(loginForm);
            hintLogin.innerHTML = `<i class="fas fa-info-circle"></i> Используйте учётную запись корпоративной сети ПНХЗ, если она у вас есть.`;
            hintLogin.parentElement.classList.remove('errorhint');

            try {
                const result = await APIClient.send('auth', 'login', data);

                if (result.status === STATUS.SUCCESS) {
                    showSuccessAndClose(modal, result.message);
                } else {
                    updateConsole();
                    showSnack(result.message);
                    hintLogin.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${result.message}`;
                    hintLogin.parentElement.classList.add('errorhint');

                    if (["invalid_password", "ldap_invalid_password"].includes(result.reason)) {
                        highlightField(loginForm.password, result.message);
                    } else if (result.reason === 'not_found') {
                        highlightField(loginForm.user, result.message);
                    }
                }
            } catch (err) {
                updateConsole();
                showSnack(err.message || 'Сбой авторизации');
            }
        });

        // ===== Показ пароля =====
        passShowControls.forEach(ctrl => {
            ctrl.addEventListener('mousedown', () => {
                ctrl.firstElementChild.className = 'fas fa-eye-slash';
                ctrl.classList.add('shine');
                passFields.forEach(input => input.type = 'text');
            });
            ['mouseup', 'mouseleave'].forEach(evt => {
                ctrl.addEventListener(evt, () => {
                    ctrl.firstElementChild.className = 'fas fa-eye';
                    ctrl.classList.remove('shine');
                    passFields.forEach(input => input.type = 'password');
                });
            });
        });

        // ===== Закрытие окна =====
        closebtn?.addEventListener('click', () => modal.remove());
        modal?.addEventListener('click', (e) => {
            if (!isNested(e.target, document.getElementById('forms_holder')) && !['checkreg', 'regstage'].includes(e.target.id)) {
                modal.remove();
            }
        });

        // ===== Этап 1: ввод пропуска =====
        bageInput?.addEventListener('input', () => {
            const value = bageInput.value;
            const parent = bageInput.closest('.input-holder');

            if (!/^[\d]*$/.test(value)) {
                bageInput.value = value.replace(/\D/g, '');
                parent.classList.add('input-error');
                setTimeout(() => parent.classList.remove('input-error'), 1200);
            } else {
                parent.classList.remove('input-error');
            }

            nextBtn.textContent = 'Найти';
            nextBtn.classList.remove('ready', 'disabled');
            nextBtn.disabled = false;
            hintText.innerHTML = value.length > 0 ? '' : '<br><i class="fas fa-info-circle"></i> Номер пропуска находится на его обратной стороне';
            hintText.className = 'hint-text';
            hintText.parentElement.classList.remove('successhint', 'errorhint');
            hintCard.classList.toggle('hidden', value.length > 0);
            foundUser = null;

            if (loginCheckInput) {
                loginCheckInput.value = '';
                loginCheckHolder.classList.add('hidden');
                hintWarn.classList.add('hidden');
            }
        });

        nextBtn?.addEventListener('click', async () => {
            const bage = bageInput.value.trim();
            if (!/^[\d]+$/.test(bage)) return;

            if (nextBtn.classList.contains('ready')) {
                wrapper.classList.add('stage2');
                return;
            }

            nextBtn.textContent = 'Поиск...';
            nextBtn.disabled = true;

            try {
                const result = await APIClient.send('perco', 'lookup', { identifier: bage });

                if (result.status === STATUS.SUCCESS) {
                    foundUser = result.data;
                    nextBtn.textContent = 'Далее';
                    nextBtn.classList.add('ready');

                    const loginSuggestion = translit(foundUser.first_name?.[0] || '') + '.' + translit(foundUser.last_name || '');
                    if (loginCheckInput) {
                        loginCheckInput.value = loginSuggestion;
                        loginCheckHolder.classList.remove('hidden');

                        const checkResult = await checkLoginExtended(loginSuggestion);
                        if (checkResult?.status !== STATUS.AVAILABLE) {
                            hintText.className = 'hint-text error';
                            hintText.parentElement.classList.add('errorhint');
                            hintWarn.classList.remove('hidden');
                            hintWarn.classList.add('error');
                            hintWarn.innerHTML = <i class="fas fa-exclamation-triangle"></i> + (checkResult.message || 'Логин занят. Измените его.');
                            nextBtn.classList.remove('ready');
                            nextBtn.disabled = true;
                        } else {
                            hintText.className = 'hint-text success';
                            hintText.parentElement.classList.add('successhint');
                            hintWarn.classList.add('hidden');
                            nextBtn.classList.add('ready');
                            nextBtn.disabled = false;
                        }

                        loginCheckInput.addEventListener('input', async function () {
                            const newLogin = this.value.trim();
                            const recheck = await checkLoginExtended(newLogin);

                            if (recheck?.status === STATUS.AVAILABLE) {
                                hintText.className = 'hint-text success';
                                hintText.parentElement.classList.add('successhint');
                                hintWarn.classList.add('hidden');
                                nextBtn.classList.add('ready');
                                nextBtn.disabled = false;
                            } else {
                                hintText.parentElement.classList.add('errorhint');
                                hintWarn.classList.remove('hidden');
                                hintWarn.classList.add('error');
                                hintWarn.innerHTML = <i class="fas fa-exclamation-triangle"></i> + (recheck.message || ' Логин занят. Измените его.');
                                nextBtn.classList.remove('ready');
                                nextBtn.disabled = true;
                            }
                        });
                    }

                    hintText.innerHTML = `<strong>Найден пользователь:</strong><br>${foundUser.first_name || ''}&nbsp;${foundUser.last_name || ''}`;
                    sendMessage(0, result.message, 'Auth.js');
                } else {
                    updateConsole();
                    hintText.className = 'hint-text error';
                    hintText.parentElement.classList.add('errorhint');
                    hintText.innerHTML = result.message;
                    nextBtn.textContent = 'Найти';
                }
            } catch (err) {
                sendMessage(3, err.message, 'Auth.js');
                showSnack(err.message);
                hintText.className = 'hint-text error';
                hintText.parentElement.classList.add('errorhint');
                hintText.innerHTML = 'Ошибка соединения с сервером.';
                nextBtn.textContent = 'Найти';
            } finally {
                nextBtn.disabled = false;
            }
        });

        // ===== Назад =====
        prevBtn?.addEventListener('click', () => wrapper.classList.remove('stage2'));
    }
};