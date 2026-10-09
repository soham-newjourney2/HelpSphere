
document.querySelectorAll(".nav-item").forEach(link => {
  if (link.href === window.location.href) {
    link.classList.add("active");
  }
});

function createHeart() {
  const heart = document.createElement("span");
  heart.classList.add("floating-heart");

  const colors = ["pink", "purple", "coral", "mint"];
  heart.classList.add(colors[Math.floor(Math.random() * colors.length)]);

  heart.innerHTML = "❤";
  heart.style.left = Math.random() * 100 + "vw";
  heart.style.animationDuration = 8 + Math.random() * 6 + "s";

  document.body.appendChild(heart);
  setTimeout(() => heart.remove(), 14000);
}

setInterval(createHeart, 700);

const menuToggle = document.getElementById("menuToggle");
const sidebar = document.querySelector(".sidebar");

const overlay = document.createElement("div");
overlay.className = "sidebar-overlay";
document.body.appendChild(overlay);

if (menuToggle) {
  menuToggle.addEventListener("click", () => {
    const open = sidebar.classList.toggle("open");
    overlay.classList.toggle("show");
    menuToggle.textContent = open ? "✖" : "☰";
  });
}

overlay.addEventListener("click", () => {
  sidebar.classList.remove("open");
  overlay.classList.remove("show");
  if (menuToggle) menuToggle.textContent = "☰";
});

/* CLOSE SIDEBAR ON NAV CLICK */
document.querySelectorAll(".nav-item").forEach(item => {
  item.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
    if (menuToggle) menuToggle.textContent = "☰";
  });
});
