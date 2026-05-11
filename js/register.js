const form = document.getElementById('regForm');
const btn = document.getElementById('submitBtn');

form.addEventListener('input', () => {
    // Basic check: if all required fields are filled, highlight button
    if (form.checkValidity()) {
        btn.classList.add('active');
        btn.style.background = "#FFBC0D";
        btn.style.color = "black";
    } else {
        btn.classList.remove('active');
        btn.style.background = "#e2e2e2";
        btn.style.color = "#999";
    }
});