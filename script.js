// Welcome message
console.log("Welcome to Student Hub!");
// LOGIN
const loginForm = document.querySelector(".login-container form");

if (loginForm) {

    loginForm.addEventListener("submit", function (event) {

        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;

        if (email === "" || password === "") {

            event.preventDefault();

            alert("Please enter your email and password.");

        }

    });

}
// SIGN UP
const signupForm = document.querySelector(".signup-container form");

if (signupForm) {

    signupForm.addEventListener("submit", function (event) {

        const password = document.getElementById("password").value;

        if (password.length < 6) {

            event.preventDefault();

            alert("Password must contain at least 6 characters.");

        } else {

            alert("Account created successfully!");

        }

    });

}



// CURRENT YEAR


const yearElements = document.querySelectorAll(".current-year");

yearElements.forEach(function (element) {

    element.textContent = new Date().getFullYear();

});

// CONFIRM LOGOUT


const logoutLinks = document.querySelectorAll('a[href="login.html"]');

logoutLinks.forEach(function (link) {

    link.addEventListener("click", function (event) {

        const confirmLogout = confirm("Are you sure you want to logout?");

        if (!confirmLogout) {

            event.preventDefault();

        }

    });

});
// login api
document.getElementById("loginForm").addEventListener("submit", function(event) {

    event.preventDefault();

    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    fetch("http://localhost:3000/api/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {

        if (data.success) {
            alert("Login successful!");
            window.location.href = "dashboard.html";
        } else {
            document.getElementById("message").innerText = "Invalid email or password";
        }

    })
    .catch(error => {
        console.log(error);
        document.getElementById("message").innerText = "Server error";
    });

});