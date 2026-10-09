// Mobile menu toggle
const toggle = document.querySelector(".menu-toggle");
const nav = document.querySelector(".nav-links");

toggle.addEventListener("click", () => {
  nav.classList.toggle("active");
});

// Close menu on link click
document.querySelectorAll(".nav-links a").forEach(link => {
  link.addEventListener("click", () => {
    nav.classList.remove("active");
  });
});

// Animated counters
const counters = document.querySelectorAll("[data-count]");
const speed = 120;

counters.forEach(counter => {
  const updateCount = () => {
    const target = +counter.getAttribute("data-count");
    const count = +counter.innerText;
    const increment = Math.ceil(target / speed);

    if (count < target) {
      counter.innerText = count + increment;
      setTimeout(updateCount, 30);
    } else {
      counter.innerText = target + "+";
    }
  };
  updateCount();
});


const dropBtn = document.getElementById('dropBtn');
const donate=document.getElementById('donation');
const loginBtn = document.getElementById('loginBtn');
const login=document.getElementById('login');

// Donate dropdown opens on clicking it (if not already open in which case it gets closed)

dropBtn.addEventListener("click", (e) => {
    if(donate.style.display=="block"){
      donate.style.display="none";
    }
    else {
      donate.style.display="block";
    }
})

//Login/Register Dropdown opens on clicking it (if not already open in which case it gets closed)

loginBtn.addEventListener("click", (e) => {
    if(login.style.display=="block"){
      login.style.display="none";
    }
    else {
      login.style.display="block";
    }
})

//Closing the dropdown when user clicks on anywhere else on the web page (if dropdown is open)

document.addEventListener("click", (e) => {
  // if click is NOT inside either dropdown, close both
  if (!dropBtn.contains(e.target) && !donate.contains(e.target) && !loginBtn.contains(e.target) && !login.contains(e.target)) {
    donate.style.display = "none";
    login.style.display = "none";
  } 
});

window.addEventListener("pageshow", function(event) {
  // Close dropdown when coming back (if dropdown remains open)
  if (donate.style.display=="block") {
    donate.style.display = "none";
  }
  if (login.style.display=="block"){
    login.style.display = "none";
  }
});