/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Version: 4.3.0
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: Common Plugins Js File
*/

//Common plugins
if (document.querySelectorAll("[toast-list]").length > 0 || document.querySelectorAll('[data-choices]').length > 0 || document.querySelectorAll("[data-provider]").length > 0) {
    let scriptContent = '';
    if (!window.Toastify) {
        scriptContent += "<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/toastify-js'></script>";
    }
    if (!window.Choices) {
        scriptContent += "<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js'></script>";
    }
    if (!window.flatpickr) {
        scriptContent += "<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/flatpickr'></script>";
    }
    if (scriptContent !== '') {
        document.write(scriptContent);
    }
}
