import { Console } from './Console.js';
import { APIClient } from './APIClient.js';

export function isNested(child, parent) {
    let node = child;
    while (node) {
        if (node === parent) return true;
        node = node.parentElement;
    }
    return false;
}

export function getFormattedDate() {
    const now = new Date();
    const pad = n => n.toString().padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

export async function sendMessage(type = 0, msg = null, source = 'User'){
    const message = {
        'level': type,
        'source': source,
        'message': msg ?? 'Действие пользователя'
    };
    try{
        await APIClient.send('console', 'add', message);
        updateConsole();
    } catch (error) {
        console.error('Ошибка отправки сообщений: ', error);
    }
}

export function updateConsole(){
    if(Console.ready && typeof Console.update === 'function'){
        Console.update();
    }
}

export function showSnack(msg) {
    let x = document.getElementById("snackbar");
    x.className = "toast";
    x.innerHTML = msg;
    setTimeout(function(){ x.className = x.className.replace("toast", ""); }, 3000);
}

export function translit(str) {
    return str.toLowerCase()
        .replace(/а/g, 'a').replace(/б/g, 'b').replace(/в/g, 'v')
        .replace(/г/g, 'g').replace(/д/g, 'd').replace(/е/g, 'e')
        .replace(/ё/g, 'e').replace(/ж/g, 'zh').replace(/з/g, 'z')
        .replace(/и/g, 'i').replace(/й/g, 'y').replace(/к/g, 'k')
        .replace(/л/g, 'l').replace(/м/g, 'm').replace(/н/g, 'n')
        .replace(/о/g, 'o').replace(/п/g, 'p').replace(/р/g, 'r')
        .replace(/с/g, 's').replace(/т/g, 't').replace(/у/g, 'u')
        .replace(/ф/g, 'f').replace(/х/g, 'h').replace(/ц/g, 'ts')
        .replace(/ч/g, 'ch').replace(/ш/g, 'sh').replace(/щ/g, 'sch')
        .replace(/ъ/g, '').replace(/ы/g, 'y').replace(/ь/g, '')
        .replace(/э/g, 'e').replace(/ю/g, 'yu').replace(/я/g, 'ya')
        .replace(/[^a-z0-9]/g, '');
}

let cachedConfig = null;

export async function getConfig() {
    if (cachedConfig) return cachedConfig;

    try {
        const response = await fetch('/config/init.json');
        if (!response.ok) throw new Error(`Config load failed: HTTP ${response.status}`);
        cachedConfig = await response.json();
        return cachedConfig;
    } catch (e) {
        console.error('Ошибка загрузки конфигурации:', e);
        return {};
    }
}