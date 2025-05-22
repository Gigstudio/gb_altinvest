// Auth.js (полная восстановленная версия с логикой login, этапами регистрации и адаптированным обработчиком bage)
import { isNested, sendMessage, showSnack, translit, updateConsole } from './Utils.js';
import { APIClient } from './APIClient.js';

const STATUS = {
    SUCCESS: 'success',
    ERROR: 'error',
    AVAILABLE: 'available',
    BAGE_EXISTS: 'bage_exists',
    EXISTS_LDAP: 'exists_ldap',
    EXISTS_LOCAL: 'exists_local',
    EXISTS_BOTH: 'exists_both'
};

function highlightField(field, message) {
    const holder = field?.parentElement;
    if (holder) {
        holder.classList.add('input-error', 'blink');
        holder.firstElementChild?.setAttribute('title', message);
    }
}

function clearFieldHighlight(field) {
    const holder = field?.parentElement;
    if (holder) {
        holder.classList.remove('input-error', 'blink');
        holder.firstElementChild?.removeAttribute('title');
    }
}

function resetErrors() {
    document.querySelectorAll('input').forEach(clearFieldHighlight);
}

function showSuccessAndClose(modal, message) {
    // sendMessage(0, message || 'Успешная авторизация', 'Auth.js::showSuccessAndClose');
    updateConsole();
    showSnack('Добро пожаловать!');
    setTimeout(() => modal.remove(), 2000);
}

function showHint(container, message, hintClass) {
    container.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
    if (hintClass) {
        container.parentElement.classList.add(hintClass);
    }
}

function setState(control, state) {
    control.disabled = state === 'disabled';
    control.classList.toggle('disabled', state === 'disabled');
}

function adaptControls(button, container, bageEmpty = true, success = false, error = false, badge = '', userFio = '', conflictMessage = '') {
    const hintText = container.querySelector('.hint-text');
    const card = container.querySelector('.card-wrapper');
    const loginSet = container.querySelector('fieldset');
    const warn = document.getElementById('hint-warn');
    const backBtn = document.getElementById('reg-back');

    const found = success;
    const conflict = error;

    if (backBtn) {
        backBtn.classList.toggle('hidden', !(found || bageEmpty));
    setState(backBtn, (found || bageEmpty) ? 'enabled' : 'disabled');
    }

    button.textContent = found && !conflict ? 'Далее' : 'Найти';
    button.classList.toggle('ready', found && !conflict);
    setState(button, bageEmpty || conflict);

    container.classList.remove('successhint', 'errorhint');
    container.firstElementChild.classList.remove('success', 'error');
    if (found && !conflict) {
        container.classList.add('successhint');
        container.firstElementChild.classList.add('success');
    }
    if (!found || conflict) {
        container.classList.add('errorhint');
        container.firstElementChild.classList.add('error');
    }

    if (hintText) {
        if (!bageEmpty) {
            if (found && !conflict) {
                hintText.innerHTML = `Пользователь найден: ${userFio}`;
            } else if (found && conflict) {
                hintText.innerHTML = `Пользователь найден: ${userFio}`;
            } else {
                hintText.innerHTML = `Пользователь PERCo-Web по номеру пропуска ${badge} не найден`;
            }
        } else {
            hintText.innerHTML = '<br><i class="fas fa-info-circle"></i> Номер пропуска находится на его обратной стороне';
        }
    }

    card?.classList.toggle('hidden', found || conflict);
    loginSet?.classList.toggle('hidden', !found);

    if (warn) {
        const showWarn = found && conflict;
        warn.classList.toggle('hidden', !showWarn);
        warn.classList.toggle('error', showWarn);
        warn.innerHTML = showWarn
            ? `<i class="fas fa-exclamation-triangle"></i> ${conflictMessage || 'Логин занят. Измените или вернитесь к форме входа.'}`
            : '';
    }
}

async function checkLoginExtended(bage, login) {
    try {
        return await APIClient.send('auth', 'checkLoginExtended', {'bage': bage, 'login': login });
    } catch (err) {
        sendMessage(3, err.message || 'Сбой обращения к серверу', 'Auth.js::checkLoginExtended');
        showSnack(err.message || 'Сбой обращения к серверу');
        return null;
    }
}

function getLoginSuggestionFromFio(fio) {
    if (!fio) return '';
    const parts = fio.trim().split(/\s+/); // Фамилия, Имя, [Отчество]
    if (parts.length < 2) return translit(fio); // fallback если что-то не так

    const lastName = parts[0]; // Фамилия
    const firstName = parts[1]; // Имя

    return translit(firstName[0] || '') + '.' + translit(lastName);
}

export const Auth = {
    initLoginUI() {
        const modal = document.getElementById('modalbg');
        const loginForm = document.forms.signin;
        const regForm = document.forms.signup;
        const closebtn = document.getElementById('close_login');
        const bageInput = document.getElementById('bage');
        const nextBtn = document.getElementById('reg-next');
        const prevBtn = document.getElementById('reg-back');
        const prevLink = document.getElementById('reg-prev');
        const hintText = document.querySelector('.inputset.hint .hint-text');
        const hintCard = document.querySelector('.inputset.hint .card-wrapper');
        const loginInput = document.getElementById('username');
        const regUserInput = document.getElementById('reguser');
        const loginCheckInput = document.getElementById('logincheck');
        const loginCheckHolder = loginCheckInput?.closest('fieldset');
        const hintWarn = document.getElementById('hint-warn');
        const wrapper = document.querySelector('.reg-wrapper');

        regForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            nextBtn?.click();
        });

        loginForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                login: loginForm.user.value.trim(),
                pass: loginForm.password.value
            };

            resetErrors();
            const hintLogin = document.querySelector('.inputset.hint .hint-info');
            hintLogin.innerHTML = `<i class="fas fa-info-circle"></i> Используйте учётную запись корпоративной сети ПНХЗ, если она у вас есть.`;
            hintLogin.parentElement.classList.remove('errorhint');

            try {
                const result = await APIClient.send('auth', 'login', data);

                if (result.status === STATUS.SUCCESS) {
                    showHint(hintLogin, result.message, 'successhint');
                    showSuccessAndClose(modal, result.message);
                } else {
                    updateConsole();
                    showSnack(result.message);
                    showHint(hintLogin, result.message, 'errorhint');

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

        bageInput?.addEventListener('input', () => {
            const value = bageInput.value.replace(/\D/g, '');
            bageInput.value = value;

            const bageEmpty = value.length === 0;
            setState(nextBtn, bageEmpty ? 'disabled' : 'enabled');
            nextBtn.textContent = 'Найти';
            nextBtn.classList.remove('ready');
            prevBtn.classList.add('hidden');

            hintText.innerHTML = '<br><i class="fas fa-info-circle"></i> Номер пропуска находится на его обратной стороне';
            hintText.classList.remove('success', 'error');
            hintText.parentElement.classList.remove('successhint', 'errorhint');
            hintCard?.classList.remove('hidden');
            loginCheckHolder?.classList.add('hidden');
            hintWarn?.classList.add('hidden');
        });

        bageInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                nextBtn?.click();
            }
        });

        nextBtn?.addEventListener('click', async () => {
            const bage = bageInput.value.trim();
            if (!/^\d+$/.test(bage)) return;

            if (nextBtn.classList.contains('ready')) {
                wrapper.classList.add('stage2');
                return;
            }

            nextBtn.textContent = 'Поиск...';
            setState(nextBtn, 'disabled');
            resetErrors();

            try {
                const result = await APIClient.send('perco', 'lookup', { identifier: bage });

                if (result.status === STATUS.SUCCESS) {
                    const foundUser = result.data;
                    const fio = foundUser.fio;
                    const loginSuggestion = getLoginSuggestionFromFio(fio);
                    // const loginSuggestion = translit(foundUser.first_name?.[0] || '') + '.' + translit(foundUser.last_name || '');
                    loginCheckInput.value = loginSuggestion;
                    regUserInput.value = loginSuggestion;
                    loginInput.value = loginSuggestion;
                    loginCheckHolder.classList.remove('hidden');

                    const checkResult = await checkLoginExtended(bage, loginSuggestion);
                    const conflict = checkResult?.status !== STATUS.AVAILABLE;
                    const status = checkResult?.status || '';
                    const message = checkResult?.message || '';

                    const isBlockedByBage = status === 'bage_exists';
                    sendMessage(conflict ? 3 : 0, checkResult.message, 'Auth.js::lookup_perco');
                    if (isBlockedByBage) {
                        highlightField(bageInput, message);
                        adaptControls(nextBtn, hintText.parentElement, false, false, true, bage, '', message);
                        return;
                    }

                    adaptControls(nextBtn, hintText.parentElement, false, true, conflict, bage, `${foundUser.last_name || ''} ${foundUser.first_name || ''}`, checkResult.message);

                    loginCheckInput.addEventListener('input', async function () {
                        const newLogin = this.value.trim();
                        const recheck = await checkLoginExtended(bage, newLogin);
                        const reConflict = recheck?.status !== STATUS.AVAILABLE;
                        const reMessage = recheck?.message || '';
                        if(!reConflict) regUserInput.value = newLogin;
                        // sendMessage(reConflict ? 3 : 0, recheck.message, 'Auth.js::recheck_login');
                        adaptControls(nextBtn, hintText.parentElement, false, true, reConflict, bage, `${foundUser.last_name || ''} ${foundUser.first_name || ''}`, reMessage);
                    });
                } else {
                    highlightField(bageInput, result.message);
                    adaptControls(nextBtn, hintText.parentElement, false, false, true, bage);
                    updateConsole();
                }
            } catch (err) {
                sendMessage(3, err.message, 'Auth.js::lookup_perco');
                showSnack(err.message);
                highlightField(bageInput, err.message);
                adaptControls(nextBtn, hintText.parentElement, false, false, true, bage);
            } finally {
                nextBtn.disabled = false;
            }
        });

        prevBtn?.addEventListener('click', () => document.getElementById('checkreg').click());
        prevLink?.addEventListener('click', () => wrapper.classList.remove('stage2'));
        closebtn?.addEventListener('click', () => modal.remove());
        modal?.addEventListener('click', (e) => {
            if (!isNested(e.target, document.getElementById('forms_holder')) && !['checkreg', 'regstage'].includes(e.target.id)) {
                modal.remove();
            }
        });
    }
};
