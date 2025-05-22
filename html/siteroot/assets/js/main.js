
import {Console} from './core/Console.js';
import { APIClient } from './core/APIClient.js';

document.addEventListener("DOMContentLoaded", function () {
    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        localStorage.setItem("theme", theme);
        themeToggle.checked = (theme === "dark");
        themeToggle.parentElement.previousElementSibling.classList.toggle("highlited",theme === "light");
        themeToggle.parentElement.nextElementSibling.classList.toggle("highlited",theme === "dark");
    }

// INIT
    const themeToggle = document.getElementById("theme-toggle");
    const inject = document.getElementById('console_inject');
    if (typeof(inject) != 'undefined' && inject != null) {
        Console.init();
    }

// Обработчики
    themeToggle.addEventListener("change", function () {
        const newTheme = themeToggle.checked ? "dark" : "light";
        applyTheme(newTheme);
    });

// START
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);
});