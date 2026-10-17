// ============================================================
// STUDENT HUB PORTAL - Common JavaScript
// One JavaScript file for all Student Hub pages
// ============================================================

document.addEventListener("DOMContentLoaded", function () {

    // ========================================================
    // 1. REGISTRATION
    // ========================================================

    const registerForm = document.querySelector('form[action="login.html"]');

    if (registerForm) {

        registerForm.addEventListener("submit", function (event) {

            event.preventDefault();

            const userId = registerForm
                .querySelector('input[name="userid"]')
                .value
                .trim();

            const password = registerForm
                .querySelector('input[name="password"]')
                .value;

            const confirmPassword = registerForm
                .querySelector('input[name="confirmpassword"]')
                .value;


            // Check empty fields
            if (userId === "" || password === "" || confirmPassword === "") {
                alert("Please fill all fields.");
                return;
            }


            // Check password length
            if (password.length < 6) {
                alert("Password must contain at least 6 characters.");
                return;
            }


            // Check password confirmation
            if (password !== confirmPassword) {
                alert("Password and Confirm Password do not match.");
                return;
            }


            // Create user object
            const user = {
                userId: userId,
                password: password
            };


            // Store user in localStorage
            localStorage.setItem(
                "studentHubUser",
                JSON.stringify(user)
            );


            alert("Registration successful! Please login.");

            // Go to login page
            window.location.href = "login.html";
        });
    }


    // ========================================================
    // 2. LOGIN
    // ========================================================

    const loginForm = document.querySelector('form[action="dashboard.html"]');

    if (loginForm) {

        loginForm.addEventListener("submit", function (event) {

            event.preventDefault();

            const userId = loginForm
                .querySelector('input[name="userid"]')
                .value
                .trim();

            const password = loginForm
                .querySelector('input[name="password"]')
                .value;


            // Check empty fields
            if (userId === "" || password === "") {
                alert("Please enter User ID and Password.");
                return;
            }


            // Get registered user
            const savedUser = localStorage.getItem("studentHubUser");


            if (!savedUser) {

                alert("No registered account found. Please register first.");

                return;
            }


            // Convert JSON string back to object
            const user = JSON.parse(savedUser);


            // Check login credentials
            if (
                user.userId === userId &&
                user.password === password
            ) {

                // Create login session
                sessionStorage.setItem(
                    "studentHubLoggedIn",
                    "true"
                );

                sessionStorage.setItem(
                    "studentHubUserId",
                    userId
                );


                alert("Login successful!");

                // Open dashboard
                window.location.href = "dashboard.html";

            } else {

                alert("Invalid User ID or Password.");

            }

        });
    }


    // ========================================================
    // 3. LOGOUT
    // ========================================================

    const logoutLinks = document.querySelectorAll(
        'a[href="login.html"]'
    );


    logoutLinks.forEach(function (link) {

        if (
            link.textContent
                .trim()
                .toLowerCase() === "logout"
        ) {

            link.addEventListener("click", function (event) {

                event.preventDefault();


                // Remove login session
                sessionStorage.removeItem(
                    "studentHubLoggedIn"
                );

                sessionStorage.removeItem(
                    "studentHubUserId"
                );


                alert("You have been logged out.");


                // Go to login page
                window.location.href = "login.html";

            });

        }

    });


    // ========================================================
    // 4. GET LOGGED-IN USER ID
    // ========================================================

    const loggedInUserId =
        sessionStorage.getItem("studentHubUserId");


    const loggedInUserElement =
        document.getElementById("loggedInUser");


    if (
        loggedInUserElement &&
        loggedInUserId
    ) {

        loggedInUserElement.textContent =
            loggedInUserId;

    }


    // ========================================================
    // 5. GENERAL FORM VALIDATION
    // ========================================================

    const allForms =
        document.querySelectorAll("form");


    allForms.forEach(function (form) {

        // Login and registration already have
        // their own validation.
        if (
            form === loginForm ||
            form === registerForm
        ) {
            return;
        }


        form.addEventListener(
            "submit",
            function (event) {

                const requiredFields =
                    form.querySelectorAll("[required]");


                let valid = true;


                requiredFields.forEach(
                    function (field) {

                        if (
                            field.value.trim() === ""
                        ) {

                            valid = false;

                            field.style.border =
                                "2px solid red";

                        } else {

                            field.style.border = "";

                        }

                    }
                );


                if (!valid) {

                    event.preventDefault();

                    alert(
                        "Please fill all required fields."
                    );

                }

            }
        );

    });


    // ========================================================
    // 6. REMOVE ERROR BORDER WHEN TYPING
    // ========================================================

    const inputs =
        document.querySelectorAll("input");


    inputs.forEach(function (input) {

        input.addEventListener(
            "input",
            function () {

                if (
                    input.value.trim() !== ""
                ) {

                    input.style.border = "";

                }

            }
        );

    });


    // ========================================================
    // 7. UPDATE FOOTER YEAR
    // ========================================================

    const footerTexts =
        document.querySelectorAll(".footer");


    footerTexts.forEach(function (footer) {

        footer.innerHTML =
            footer.innerHTML.replace(
                /©\s*\d{4}/,
                "© " +
                new Date().getFullYear()
            );

    });


    // ========================================================
    // 8. LOGIN STATUS
    // ========================================================

    const isLoggedIn =
        sessionStorage.getItem(
            "studentHubLoggedIn"
        );


    if (isLoggedIn === "true") {

        console.log(
            "Student Hub: User is logged in."
        );

    } else {

        console.log(
            "Student Hub: No active login session."
        );

    }


    // ========================================================
    // 9. CURRENT PAGE INFORMATION
    // ========================================================

    console.log(
        "Student Hub Portal loaded:",
        window.location.pathname
    );

});