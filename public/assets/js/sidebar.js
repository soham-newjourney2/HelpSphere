const menuToggle = document.getElementById("menuToggle");
const sidebarClose = document.getElementById("sidebarClose");
const overlay = document.getElementById("overlay");

menuToggle.addEventListener("click", () => {
  document.body.classList.add("sidebar-open");
});

sidebarClose.addEventListener("click", () => {
  document.body.classList.remove("sidebar-open");
});

overlay.addEventListener("click", () => {
  document.body.classList.remove("sidebar-open");
});
