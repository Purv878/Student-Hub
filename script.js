// document.addEventListener("DOMContentLoaded", function () {
//     const registerForm = document.querySelector("#registrationForm") ||
//         document.querySelector('form[action="login.html"]');

//     const loginForm = document.querySelector('form[action="dashboard.html"]');

//     function showError(id, message) {
//         const element = document.getElementById(id);
//         if (element) element.textContent = message;
//     }

//     function clearErrors(form) {
//         form.querySelectorAll(".error-message").forEach(function (element) {
//             element.textContent = "";
//         });
//     }

//     function setError(field, id, message) {
//         showError(id, message);
//         if (field) field.setAttribute("aria-invalid", "true");
//     }

//     if (registerForm) {
//         const passwordField = registerForm.querySelector('[name="password"]');
//         const strengthElement = document.getElementById("passwordStrength");

//         if (passwordField && strengthElement) {
//             passwordField.addEventListener("input", function () {
//                 const value = passwordField.value;
//                 let score = 0;

//                 if (value.length >= 8) score++;
//                 if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
//                 if (/[0-9]/.test(value)) score++;
//                 if (/[^A-Za-z0-9]/.test(value)) score++;

//                 if (!value) {
//                     strengthElement.textContent = "";
//                     strengthElement.style.color = "";
//                 } else if (score <= 1) {
//                     strengthElement.textContent = "Password strength: Weak";
//                     strengthElement.style.color = "#d00000";
//                 } else if (score <= 3) {
//                     strengthElement.textContent = "Password strength: Medium";
//                     strengthElement.style.color = "#b87500";
//                 } else {
//                     strengthElement.textContent = "Password strength: Strong";
//                     strengthElement.style.color = "#16803c";
//                 }
//             });
//         }

//         registerForm.addEventListener("submit", function (event) {
//             event.preventDefault();
//             clearErrors(registerForm);

//             registerForm.querySelectorAll("[aria-invalid]").forEach(function (field) {
//                 field.removeAttribute("aria-invalid");
//             });

//             let valid = true;

//             const fullNameField = registerForm.querySelector('[name="fullname"]');
//             const emailField = registerForm.querySelector('[name="email"]');
//             const mobileField = registerForm.querySelector('[name="mobile"]');
//             const userIdField = registerForm.querySelector('[name="userid"]');
//             const confirmField = registerForm.querySelector('[name="confirmpassword"]');
//             const courseField = registerForm.querySelector('[name="course"]');
//             const yearField = registerForm.querySelector('[name="year"]');
//             const termsField = registerForm.querySelector('[name="terms"]');

//             function check(field, errorId, message) {
//                 if (field && !field.value.trim()) {
//                     setError(field, errorId, message);
//                     valid = false;
//                 }
//             }

//             if (fullNameField) {
//                 const name = fullNameField.value.trim();

//                 if (!name) {
//                     setError(fullNameField, "fullnameError", "Please enter your full name.");
//                     valid = false;
//                 } else if (!/^[A-Za-z][A-Za-z .'-]{1,59}$/.test(name)) {
//                     setError(fullNameField, "fullnameError", "Enter a valid name using letters.");
//                     valid = false;
//                 }
//             }

//             if (emailField) {
//                 const email = emailField.value.trim();

//                 if (!email) {
//                     setError(emailField, "emailError", "Please enter your email.");
//                     valid = false;
//                 } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
//                     setError(emailField, "emailError", "Please enter a valid email address.");
//                     valid = false;
//                 }
//             }

//             if (mobileField) {
//                 if (!/^[6-9]\d{9}$/.test(mobileField.value.trim())) {
//                     setError(mobileField, "mobileError", "Enter a valid 10-digit mobile number.");
//                     valid = false;
//                 }
//             }

//             check(userIdField, "useridError", "Please enter a User ID.");

//             if (passwordField) {
//                 const password = passwordField.value;

//                 if (password.length < 8) {
//                     setError(passwordField, "passwordError", "Password must contain at least 8 characters.");
//                     valid = false;
//                 } else if (
//                     !/[A-Z]/.test(password) ||
//                     !/[a-z]/.test(password) ||
//                     !/[0-9]/.test(password) ||
//                     !/[^A-Za-z0-9]/.test(password)
//                 ) {
//                     setError(passwordField, "passwordError", "Use uppercase, lowercase, a number and a symbol.");
//                     valid = false;
//                 }
//             }

//             if (confirmField && passwordField) {
//                 if (!confirmField.value) {
//                     setError(confirmField, "confirmPasswordError", "Please confirm your password.");
//                     valid = false;
//                 } else if (confirmField.value !== passwordField.value) {
//                     setError(confirmField, "confirmPasswordError", "Passwords do not match.");
//                     valid = false;
//                 }
//             }

//             check(courseField, "courseError", "Please select a course.");
//             check(yearField, "yearError", "Please select your year.");

//             const genderField = registerForm.querySelector('[name="gender"]');

//             if (genderField && !registerForm.querySelector('[name="gender"]:checked')) {
//                 setError(genderField, "genderError", "Please select your gender.");
//                 valid = false;
//             }

//             if (termsField && !termsField.checked) {
//                 setError(termsField, "termsError", "Please accept the terms and conditions.");
//                 valid = false;
//             }

//             if (!valid) {
//                 const firstInvalid = registerForm.querySelector('[aria-invalid="true"]');
//                 if (firstInvalid) firstInvalid.focus();
//                 return;
//             }

//             const user = {
//                 userId: userIdField ? userIdField.value.trim() : "",
//                 password: passwordField ? passwordField.value : "",
//                 fullname: fullNameField ? fullNameField.value.trim() : "",
//                 email: emailField ? emailField.value.trim() : "",
//                 mobile: mobileField ? mobileField.value.trim() : "",
//                 course: courseField ? courseField.value : "",
//                 year: yearField ? yearField.value : "",
//                 gender: (registerForm.querySelector('[name="gender"]:checked') || {}).value || ""
//             };

//             try {
//                 localStorage.setItem("studentHubUser", JSON.stringify(user));
//             } catch (error) {
//                 alert("Unable to save registration data in this browser.");
//                 return;
//             }

//             alert("Registration successful! Please login.");
//             window.location.href = "login.html";
//         });
//     }

//     if (loginForm) {
//         loginForm.addEventListener("submit", function (event) {
//             event.preventDefault();

//             const userIdField = loginForm.querySelector('[name="userid"]');
//             const passwordField = loginForm.querySelector('[name="password"]');

//             if (
//                 !userIdField ||
//                 !passwordField ||
//                 !userIdField.value.trim() ||
//                 !passwordField.value
//             ) {
//                 alert("Please enter User ID and Password.");
//                 return;
//             }

//             let user;

//             try {
//                 user = JSON.parse(localStorage.getItem("studentHubUser") || "null");
//             } catch (error) {
//                 user = null;
//             }

//             if (!user) {
//                 alert("No registered account found. Please register first.");
//                 return;
//             }

//             if (
//                 user.userId === userIdField.value.trim() &&
//                 user.password === passwordField.value
//             ) {
//                 sessionStorage.setItem("studentHubLoggedIn", "true");
//                 sessionStorage.setItem("studentHubUserId", user.userId);

//                 alert("Login successful!");
//                 window.location.href = "dashboard.html";
//             } else {
//                 alert("Invalid User ID or Password.");
//             }
//         });
//     }

//     document.querySelectorAll('a[href="login.html"]').forEach(function (link) {
//         if (link.textContent.trim().toLowerCase() === "logout") {
//             link.addEventListener("click", function (event) {
//                 event.preventDefault();

//                 sessionStorage.removeItem("studentHubLoggedIn");
//                 sessionStorage.removeItem("studentHubUserId");

//                 alert("You have been logged out.");
//                 window.location.href = "login.html";
//             });
//         }
//     });

//     const loggedInUserId = sessionStorage.getItem("studentHubUserId");
//     const loggedInUserElement = document.getElementById("loggedInUser");

//     if (loggedInUserElement && loggedInUserId) {
//         loggedInUserElement.textContent = loggedInUserId;
//     }

//     document.querySelectorAll("form").forEach(function (form) {
//         if (form === loginForm || form === registerForm) return;

//         form.addEventListener("submit", function (event) {
//             let valid = true;

//             form.querySelectorAll("[required]").forEach(function (field) {
//                 const fieldValid =
//                     field.type === "checkbox" || field.type === "radio"
//                         ? field.checked
//                         : field.value.trim() !== "";

//                 if (!fieldValid) {
//                     valid = false;
//                     field.style.border = "2px solid red";
//                 } else {
//                     field.style.border = "";
//                 }
//             });

//             if (!valid) {
//                 event.preventDefault();
//                 alert("Please fill all required fields.");
//             }
//         });
//     });

//     document.querySelectorAll("input, select, textarea").forEach(function (field) {
//         field.addEventListener("input", function () {
//             if (field.value.trim() !== "") {
//                 field.style.border = "";
//             }
//         });

//         field.addEventListener("change", function () {
//             if (
//                 (field.type === "checkbox" || field.type === "radio") &&
//                 field.checked
//             ) {
//                 field.style.border = "";
//             } else if (field.value.trim() !== "") {
//                 field.style.border = "";
//             }
//         });
//     });

//     document.querySelectorAll(".footer").forEach(function (footer) {
//         footer.innerHTML = footer.innerHTML.replace(
//             /©\s*\d{4}/,
//             "© " + new Date().getFullYear()
//         );
//     });

//     const themeKey = "studentHubTheme";
//     const themeButton = document.querySelector("[data-theme-toggle]");

//     function applyTheme(theme) {
//         document.documentElement.dataset.theme = theme;
//         document.body.dataset.theme = theme;

//         if (themeButton) {
//             themeButton.setAttribute("aria-pressed", String(theme === "dark"));
//             themeButton.textContent =
//                 theme === "dark" ? "☀️ Light Mode" : "🌙 Dark Mode";
//         }
//     }

//     let savedTheme = "light";

//     try {
//         savedTheme = localStorage.getItem(themeKey) === "dark" ? "dark" : "light";
//     } catch (error) {
//         savedTheme = "light";
//     }

//     applyTheme(savedTheme);

//     if (themeButton) {
//         themeButton.addEventListener("click", function () {
//             const nextTheme =
//                 document.documentElement.dataset.theme === "dark" ? "light" : "dark";

//             try {
//                 localStorage.setItem(themeKey, nextTheme);
//             } catch (error) {
//                 console.warn("Theme preference could not be saved.");
//             }

//             applyTheme(nextTheme);
//         });
//     }

//     document.querySelectorAll("[data-faq-question]").forEach(function (question) {
//         question.setAttribute("aria-expanded", "false");

//         const answerId = question.getAttribute("aria-controls");
//         const answer = answerId
//             ? document.getElementById(answerId)
//             : question.nextElementSibling;

//         if (answer) answer.hidden = true;

//         question.addEventListener("click", function () {
//             const target = answerId
//                 ? document.getElementById(answerId)
//                 : question.nextElementSibling;

//             if (!target) return;

//             const isOpen = question.getAttribute("aria-expanded") === "true";

//             question.setAttribute("aria-expanded", String(!isOpen));
//             target.hidden = isOpen;
//         });
//     });

//     const modal = document.querySelector("[data-modal]");

//     document.querySelectorAll("[data-modal-open]").forEach(function (button) {
//         button.addEventListener("click", function () {
//             if (!modal) return;

//             modal.hidden = false;

//             const focusable = modal.querySelector(
//                 "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])"
//             );

//             if (focusable) focusable.focus();
//         });
//     });

//     function closeModal() {
//         if (modal) modal.hidden = true;
//     }

//     document.querySelectorAll("[data-modal-close]").forEach(function (button) {
//         button.addEventListener("click", closeModal);
//     });

//     if (modal) {
//         modal.addEventListener("click", function (event) {
//             if (event.target === modal) closeModal();
//         });
//     }

//     document.addEventListener("keydown", function (event) {
//         if (event.key === "Escape") closeModal();
//     });

//     const slides = Array.from(document.querySelectorAll("[data-slide]"));

//     let currentSlide = slides.findIndex(function (slide) {
//         return !slide.hidden;
//     });

//     if (slides.length > 0) {
//         if (currentSlide < 0) currentSlide = 0;

//         function showSlide(index) {
//             currentSlide = (index + slides.length) % slides.length;

//             slides.forEach(function (slide, slideIndex) {
//                 slide.hidden = slideIndex !== currentSlide;
//                 slide.setAttribute("aria-hidden", String(slideIndex !== currentSlide));
//             });
//         }

//         document.querySelectorAll("[data-slide-next]").forEach(function (button) {
//             button.addEventListener("click", function () {
//                 showSlide(currentSlide + 1);
//             });
//         });

//         document.querySelectorAll("[data-slide-prev]").forEach(function (button) {
//             button.addEventListener("click", function () {
//                 showSlide(currentSlide - 1);
//             });
//         });

//         showSlide(currentSlide);
//     }

//     const notification = document.querySelector("[data-notification]");
//     const notificationClose = document.querySelector("[data-notification-close]");

//     if (notificationClose && notification) {
//         notificationClose.addEventListener("click", function () {
//             notification.hidden = true;
//         });
//     }

//     const menuButton = document.querySelector("[data-menu-toggle]");
//     const menu = document.querySelector("[data-menu]");

//     if (menuButton && menu) {
//         menuButton.setAttribute("aria-expanded", "false");

//         menuButton.addEventListener("click", function () {
//             const isExpanded =
//                 menuButton.getAttribute("aria-expanded") === "true";

//             menuButton.setAttribute("aria-expanded", String(!isExpanded));
//             menu.hidden = isExpanded;
//         });
//     }

//     console.log("Student Hub Portal loaded:", window.location.pathname);

//     console.log(
//         sessionStorage.getItem("studentHubLoggedIn") === "true"
//             ? "Student Hub: User is logged in."
//             : "Student Hub: No active login session."
//     );
// });
document.addEventListener("DOMContentLoaded", function () {
    const registerForm = document.querySelector("#registrationForm") ||
        document.querySelector('form[action="login.html"]');

    const loginForm = document.querySelector('form[action="dashboard.html"]');

    function showError(id, message) {
        const element = document.getElementById(id);
        if (element) element.textContent = message;
    }

    function clearErrors(form) {
        form.querySelectorAll(".error-message").forEach(function (element) {
            element.textContent = "";
        });
    }

    function setError(field, id, message) {
        showError(id, message);
        if (field) field.setAttribute("aria-invalid", "true");
    }

    if (registerForm) {
        const passwordField = registerForm.querySelector('[name="password"]');
        const strengthElement = document.getElementById("passwordStrength");

        if (passwordField && strengthElement) {
            passwordField.addEventListener("input", function () {
                const value = passwordField.value;
                let score = 0;

                if (value.length >= 8) score++;
                if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
                if (/[0-9]/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value)) score++;

                if (!value) {
                    strengthElement.textContent = "";
                    strengthElement.style.color = "";
                } else if (score <= 1) {
                    strengthElement.textContent = "Password strength: Weak";
                    strengthElement.style.color = "#d00000";
                } else if (score <= 3) {
                    strengthElement.textContent = "Password strength: Medium";
                    strengthElement.style.color = "#b87500";
                } else {
                    strengthElement.textContent = "Password strength: Strong";
                    strengthElement.style.color = "#16803c";
                }
            });
        }

        registerForm.addEventListener("submit", function (event) {
            event.preventDefault();
            clearErrors(registerForm);

            registerForm.querySelectorAll("[aria-invalid]").forEach(function (field) {
                field.removeAttribute("aria-invalid");
            });

            let valid = true;

            const fullNameField = registerForm.querySelector('[name="fullname"]');
            const emailField = registerForm.querySelector('[name="email"]');
            const mobileField = registerForm.querySelector('[name="mobile"]');
            const userIdField = registerForm.querySelector('[name="userid"]');
            const confirmField = registerForm.querySelector('[name="confirmpassword"]');
            const courseField = registerForm.querySelector('[name="course"]');
            const yearField = registerForm.querySelector('[name="year"]');
            const termsField = registerForm.querySelector('[name="terms"]');

            function check(field, errorId, message) {
                if (field && !field.value.trim()) {
                    setError(field, errorId, message);
                    valid = false;
                }
            }

            if (fullNameField) {
                const name = fullNameField.value.trim();

                if (!name) {
                    setError(fullNameField, "fullnameError", "Please enter your full name.");
                    valid = false;
                } else if (!/^[A-Za-z][A-Za-z .'-]{1,59}$/.test(name)) {
                    setError(fullNameField, "fullnameError", "Enter a valid name using letters.");
                    valid = false;
                }
            }

            if (emailField) {
                const email = emailField.value.trim();

                if (!email) {
                    setError(emailField, "emailError", "Please enter your email.");
                    valid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    setError(emailField, "emailError", "Please enter a valid email address.");
                    valid = false;
                }
            }

            if (mobileField) {
                if (!/^[6-9]\d{9}$/.test(mobileField.value.trim())) {
                    setError(mobileField, "mobileError", "Enter a valid 10-digit mobile number.");
                    valid = false;
                }
            }

            check(userIdField, "useridError", "Please enter a User ID.");

            if (passwordField) {
                const password = passwordField.value;

                if (password.length < 8) {
                    setError(passwordField, "passwordError", "Password must contain at least 8 characters.");
                    valid = false;
                } else if (
                    !/[A-Z]/.test(password) ||
                    !/[a-z]/.test(password) ||
                    !/[0-9]/.test(password) ||
                    !/[^A-Za-z0-9]/.test(password)
                ) {
                    setError(passwordField, "passwordError", "Use uppercase, lowercase, a number and a symbol.");
                    valid = false;
                }
            }

            if (confirmField && passwordField) {
                if (!confirmField.value) {
                    setError(confirmField, "confirmPasswordError", "Please confirm your password.");
                    valid = false;
                } else if (confirmField.value !== passwordField.value) {
                    setError(confirmField, "confirmPasswordError", "Passwords do not match.");
                    valid = false;
                }
            }

            check(courseField, "courseError", "Please select a course.");
            check(yearField, "yearError", "Please select your year.");

            const genderField = registerForm.querySelector('[name="gender"]');

            if (genderField && !registerForm.querySelector('[name="gender"]:checked')) {
                setError(genderField, "genderError", "Please select your gender.");
                valid = false;
            }

            if (termsField && !termsField.checked) {
                setError(termsField, "termsError", "Please accept the terms and conditions.");
                valid = false;
            }

            if (!valid) {
                const firstInvalid = registerForm.querySelector('[aria-invalid="true"]');
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            const user = {
                userId: userIdField ? userIdField.value.trim() : "",
                password: passwordField ? passwordField.value : "",
                fullname: fullNameField ? fullNameField.value.trim() : "",
                email: emailField ? emailField.value.trim() : "",
                mobile: mobileField ? mobileField.value.trim() : "",
                course: courseField ? courseField.value : "",
                year: yearField ? yearField.value : "",
                gender: (registerForm.querySelector('[name="gender"]:checked') || {}).value || ""
            };

            try {
                localStorage.setItem("studentHubUser", JSON.stringify(user));
            } catch (error) {
                alert("Unable to save registration data in this browser.");
                return;
            }

            alert("Registration successful! Please login.");
            window.location.href = "login.html";
        });
    }

    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const userIdField = loginForm.querySelector('[name="userid"]');
            const passwordField = loginForm.querySelector('[name="password"]');

            if (
                !userIdField ||
                !passwordField ||
                !userIdField.value.trim() ||
                !passwordField.value
            ) {
                alert("Please enter User ID and Password.");
                return;
            }

            let user;

            try {
                user = JSON.parse(localStorage.getItem("studentHubUser") || "null");
            } catch (error) {
                user = null;
            }

            if (!user) {
                alert("No registered account found. Please register first.");
                return;
            }

            if (
                user.userId === userIdField.value.trim() &&
                user.password === passwordField.value
            ) {
                sessionStorage.setItem("studentHubLoggedIn", "true");
                sessionStorage.setItem("studentHubUserId", user.userId);

                alert("Login successful!");
                window.location.href = "dashboard.html";
            } else {
                alert("Invalid User ID or Password.");
            }
        });
    }

    document.querySelectorAll('a[href="login.html"]').forEach(function (link) {
        if (link.textContent.trim().toLowerCase() === "logout") {
            link.addEventListener("click", function (event) {
                event.preventDefault();

                sessionStorage.removeItem("studentHubLoggedIn");
                sessionStorage.removeItem("studentHubUserId");

                alert("You have been logged out.");
                window.location.href = "login.html";
            });
        }
    });

    const loggedInUserId = sessionStorage.getItem("studentHubUserId");
    const loggedInUserElement = document.getElementById("loggedInUser");

    if (loggedInUserElement && loggedInUserId) {
        loggedInUserElement.textContent = loggedInUserId;
    }

    document.querySelectorAll("form").forEach(function (form) {
        if (form === loginForm || form === registerForm) return;

        form.addEventListener("submit", function (event) {
            let valid = true;

            form.querySelectorAll("[required]").forEach(function (field) {
                const fieldValid =
                    field.type === "checkbox" || field.type === "radio"
                        ? field.checked
                        : field.value.trim() !== "";

                if (!fieldValid) {
                    valid = false;
                    field.style.border = "2px solid red";
                } else {
                    field.style.border = "";
                }
            });

            if (!valid) {
                event.preventDefault();
                alert("Please fill all required fields.");
            }
        });
    });

    document.querySelectorAll("input, select, textarea").forEach(function (field) {
        field.addEventListener("input", function () {
            if (field.value.trim() !== "") {
                field.style.border = "";
            }
        });

        field.addEventListener("change", function () {
            if (
                (field.type === "checkbox" || field.type === "radio") &&
                field.checked
            ) {
                field.style.border = "";
            } else if (field.value.trim() !== "") {
                field.style.border = "";
            }
        });
    });

    document.querySelectorAll(".footer").forEach(function (footer) {
        footer.innerHTML = footer.innerHTML.replace(
            /©\s*\d{4}/,
            "© " + new Date().getFullYear()
        );
    });

    const themeKey = "studentHubTheme";
    const themeButton = document.querySelector("[data-theme-toggle]");

    function applyTheme(theme) {
        document.documentElement.dataset.theme = theme;
        document.body.dataset.theme = theme;

        if (themeButton) {
            themeButton.setAttribute("aria-pressed", String(theme === "dark"));
            themeButton.textContent =
                theme === "dark" ? "☀️ Light Mode" : "🌙 Dark Mode";
        }
    }

    let savedTheme = "light";

    try {
        savedTheme = localStorage.getItem(themeKey) === "dark" ? "dark" : "light";
    } catch (error) {
        savedTheme = "light";
    }

    applyTheme(savedTheme);

    if (themeButton) {
        themeButton.addEventListener("click", function () {
            const nextTheme =
                document.documentElement.dataset.theme === "dark" ? "light" : "dark";

            try {
                localStorage.setItem(themeKey, nextTheme);
            } catch (error) {
                console.warn("Theme preference could not be saved.");
            }

            applyTheme(nextTheme);
        });
    }

    document.querySelectorAll("[data-faq-question]").forEach(function (question) {
        question.setAttribute("aria-expanded", "false");

        const answerId = question.getAttribute("aria-controls");
        const answer = answerId
            ? document.getElementById(answerId)
            : question.nextElementSibling;

        if (answer) answer.hidden = true;

        question.addEventListener("click", function () {
            const target = answerId
                ? document.getElementById(answerId)
                : question.nextElementSibling;

            if (!target) return;

            const isOpen = question.getAttribute("aria-expanded") === "true";

            question.setAttribute("aria-expanded", String(!isOpen));
            target.hidden = isOpen;
        });
    });

    const modal = document.querySelector("[data-modal]");

    document.querySelectorAll("[data-modal-open]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal) return;

            modal.hidden = false;

            const focusable = modal.querySelector(
                "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])"
            );

            if (focusable) focusable.focus();
        });
    });

    function closeModal() {
        if (modal) modal.hidden = true;
    }

    document.querySelectorAll("[data-modal-close]").forEach(function (button) {
        button.addEventListener("click", closeModal);
    });

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) closeModal();
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeModal();
    });

    const slides = Array.from(document.querySelectorAll("[data-slide]"));

    let currentSlide = slides.findIndex(function (slide) {
        return !slide.hidden;
    });

    if (slides.length > 0) {
        if (currentSlide < 0) currentSlide = 0;

        function showSlide(index) {
            currentSlide = (index + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                slide.hidden = slideIndex !== currentSlide;
                slide.setAttribute("aria-hidden", String(slideIndex !== currentSlide));
            });
        }

        document.querySelectorAll("[data-slide-next]").forEach(function (button) {
            button.addEventListener("click", function () {
                showSlide(currentSlide + 1);
            });
        });

        document.querySelectorAll("[data-slide-prev]").forEach(function (button) {
            button.addEventListener("click", function () {
                showSlide(currentSlide - 1);
            });
        });

        showSlide(currentSlide);
    }

    const notification = document.querySelector("[data-notification]");
    const notificationClose = document.querySelector("[data-notification-close]");

    if (notificationClose && notification) {
        notificationClose.addEventListener("click", function () {
            notification.hidden = true;
        });
    }

    const menuButton = document.querySelector("[data-menu-toggle]");
    const menu = document.querySelector("[data-menu]");

    if (menuButton && menu) {
        menuButton.setAttribute("aria-expanded", "false");

        menuButton.addEventListener("click", function () {
            const isExpanded =
                menuButton.getAttribute("aria-expanded") === "true";

            menuButton.setAttribute("aria-expanded", String(!isExpanded));
            menu.classList.toggle("active", !isExpanded);
        });
    }

    console.log("Student Hub Portal loaded:", window.location.pathname);

    console.log(
        sessionStorage.getItem("studentHubLoggedIn") === "true"
            ? "Student Hub: User is logged in."
            : "Student Hub: No active login session."
    );
});

document.addEventListener("DOMContentLoaded", function () {
    const pageSize = 6;

    async function fetchJSON(url) {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error("Could not load " + url + " (HTTP " + response.status + ")");
        }
        return response.json();
    }

    function setupDataList(config) {
        const searchInput = document.getElementById(config.searchId);
        const filterInput = document.getElementById(config.filterId);
        const sortInput = document.getElementById(config.sortId);
        const list = document.getElementById(config.listId);
        const status = document.getElementById(config.statusId);
        const prevButton = document.getElementById(config.prevId);
        const nextButton = document.getElementById(config.nextId);
        const pageInfo = document.getElementById(config.pageInfoId);

        let allItems = [];
        let currentPage = 1;

        function populateFilter() {
            const values = [...new Set(allItems.map(item => item[config.filterKey]))].sort();
            values.forEach(value => {
                const option = document.createElement("option");
                option.value = value;
                option.textContent = value;
                filterInput.appendChild(option);
            });
        }

        function getFilteredItems() {
            const query = searchInput.value.trim().toLowerCase();
            const category = filterInput.value;
            const items = allItems.filter(item => {
                const matchesQuery = config.searchKeys.some(key =>
                    String(item[key]).toLowerCase().includes(query)
                );
                return matchesQuery && (!category || item[config.filterKey] === category);
            });

            const [sortKey, direction] = sortInput.value.split("-");
            items.sort((a, b) => {
                let first = a[sortKey];
                let second = b[sortKey];

                if (sortKey === "date" || sortKey === "id") {
                    first = sortKey === "id" ? Number(first) : new Date(first).getTime();
                    second = sortKey === "id" ? Number(second) : new Date(second).getTime();
                    return direction === "desc" ? second - first : first - second;
                }

                const result = String(first).localeCompare(String(second));
                return direction === "desc" ? -result : result;
            });

            return items;
        }

        function render() {
            const items = getFilteredItems();
            const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            const visibleItems = items.slice((currentPage - 1) * pageSize, currentPage * pageSize);

            list.replaceChildren();

            if (visibleItems.length === 0) {
                status.textContent = "No matching records found.";
            } else {
                status.textContent = "Showing " + visibleItems.length + " of " + items.length + " records.";
            }

            visibleItems.forEach(item => {
                const card = document.createElement("article");
                card.className = "data-card";

                config.fields.forEach(field => {
                    const line = document.createElement(field.heading ? "h3" : "p");
                    line.textContent = field.label + item[field.key];
                    card.appendChild(line);
                });

                list.appendChild(card);
            });

            pageInfo.textContent = "Page " + currentPage + " of " + totalPages;
            prevButton.disabled = currentPage <= 1;
            nextButton.disabled = currentPage >= totalPages;
        }

        status.textContent = "Loading data...";

        fetchJSON(config.url)
            .then(data => {
                if (!Array.isArray(data)) throw new Error("JSON data must be an array.");
                allItems = data;
                populateFilter();
                render();
            })
            .catch(error => {
                status.textContent = "Unable to load data. Check that the JSON file exists and run the site using Live Server.";
                console.error(error);
            });

        searchInput.addEventListener("input", () => {
            currentPage = 1;
            render();
        });
        filterInput.addEventListener("change", () => {
            currentPage = 1;
            render();
        });
        sortInput.addEventListener("change", () => {
            currentPage = 1;
            render();
        });
        prevButton.addEventListener("click", () => {
            if (currentPage > 1) currentPage--;
            render();
        });
        nextButton.addEventListener("click", () => {
            currentPage++;
            render();
        });
    }

    setupDataList({
        url: "events.json",
        searchId: "eventSearch", filterId: "eventCategory", sortId: "eventSort",
        listId: "eventList", statusId: "eventStatus", prevId: "eventPrev",
        nextId: "eventNext", pageInfoId: "eventPageInfo",
        filterKey: "category", searchKeys: ["title", "category", "location"],
        fields: [
            { key: "title", label: "", heading: true },
            { key: "category", label: "Category: " },
            { key: "date", label: "Date: " },
            { key: "location", label: "Location: " }
        ]
    });

    setupDataList({
        url: "students.json",
        searchId: "studentSearch", filterId: "studentCourse", sortId: "studentSort",
        listId: "studentList", statusId: "studentStatus", prevId: "studentPrev",
        nextId: "studentNext", pageInfoId: "studentPageInfo",
        filterKey: "course", searchKeys: ["name", "studentId", "course"],
        fields: [
            { key: "name", label: "", heading: true },
            { key: "studentId", label: "Student ID: " },
            { key: "course", label: "Course: " },
            { key: "year", label: "Year: " }
        ]
    });

    const faqList = document.getElementById("jsonFaqList");
    const faqStatus = document.getElementById("faqStatus");

    fetchJSON("faqs.json")
        .then(faqs => {
            if (!Array.isArray(faqs)) throw new Error("FAQ JSON data must be an array.");
            faqStatus.textContent = "Loaded " + faqs.length + " FAQs.";
            faqs.forEach((faq, index) => {
                const item = document.createElement("div");
                item.className = "faq-item";

                const question = document.createElement("button");
                question.type = "button";
                question.className = "faq-question";
                question.textContent = faq.question;
                question.setAttribute("aria-expanded", "false");
                question.setAttribute("aria-controls", "json-faq-answer-" + index);

                const answer = document.createElement("div");
                answer.className = "faq-answer";
                answer.id = "json-faq-answer-" + index;
                answer.hidden = true;
                answer.textContent = faq.answer;

                question.addEventListener("click", () => {
                    const expanded = question.getAttribute("aria-expanded") === "true";
                    question.setAttribute("aria-expanded", String(!expanded));
                    answer.hidden = expanded;
                });

                item.append(question, answer);
                faqList.appendChild(item);
            });
        })
        .catch(error => {
            faqStatus.textContent = "Unable to load FAQs. Check faqs.json and run the site using Live Server.";
            console.error(error);
        });
});
