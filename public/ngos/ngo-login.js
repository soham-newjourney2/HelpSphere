const loginForm = document.querySelector(".login-form");
const loginError = document.querySelector(".login-error");
const loginFields = document.querySelectorAll(".login-field");
const loginButton = document.querySelector(".login-submit");

loginForm.addEventListener("submit", (e) => {
  e.preventDefault();

  loginError.classList.remove("active");
  loginFields.forEach(field => field.classList.remove("error"));

  const email = loginFields[0].querySelector("input").value.trim();
  const password = loginFields[1].querySelector("input").value.trim();

  if (!email || !password) {
    triggerError();
    return;
  }

  loginButton.classList.add("loading");

  setTimeout(() => {
    if (email !== "ngo@demo.com" || password !== "password123") {
      loginButton.classList.remove("loading");
      triggerError();
      return;
    }

    window.location.href = "dashboard.html";
  }, 900);
});

function triggerError() {
  loginError.classList.add("active");
  loginFields.forEach(field => field.classList.add("error"));
}
