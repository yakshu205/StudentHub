let login = document.getElementById("login-form");

if (login) {

    login.addEventListener("submit", function(event) {

        event.preventDefault();

        let email = document.getElementById("email").value.trim();
        let password = document.getElementById("password").value.trim();

        let notification = document.getElementById("notification");

        if (email === "" || password === "") {

            notification.textContent = "Please fill in all fields.";

            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");

            notification.style.display = "block";

            setTimeout(function() {
                notification.style.display = "none";
            }, 3000);

        } else {

            notification.textContent = "Login successful! Redirecting...";

            notification.classList.remove("notification-error");
            notification.classList.add("notification-success");

            notification.style.display = "block";

            setTimeout(function() {
                window.location.href = "dashboard.html";
            }, 2000);

        }

    });

}

let register = document.getElementById("register-form");

if (register) {
    register.addEventListener("submit", function(event) {
        event.preventDefault();
        let name = document.getElementById("name").value.trim();

        let email = document.getElementById("email").value.trim();
        let mobile = document.getElementById("mobile").value.trim();
        let password = document.getElementById("password").value.trim();
        let confirmPassword = document.getElementById("confirm-password").value.trim();
        let dob = document.getElementById("dob").value.trim();
        let gender = document.querySelector('input[name="gender"]:checked') ? document.querySelector('input[name="gender"]:checked').value : "";
        let course = document.getElementById("course").value.trim();
        let skills = Array.from(document.querySelectorAll('input[name="skills"]:checked')).map(skill => skill.value);
        let address = document.getElementById("address").value.trim();

        // check terms and condition is selected or not ;
        let terms = document.getElementById("terms").checked;
        if(name === ""){
            let notification = document.getElementById("notification");
            notification.textContent = "Please fill in the name field.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }if(email === ""){
            let notification = document.getElementById("notification");
            notification.textContent = "Please fill in the email field.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }

        if(password !== confirmPassword || !terms) {
            let notification = document.getElementById("notification");
            notification.textContent = "Passwords do not match.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }
        if(dob === ""){
            let notification = document.getElementById("notification");
            notification.textContent = "Please fill in the date of birth field.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }
        if(gender === ""){
            let notification = document.getElementById("notification");
            notification.textContent = "Please select a gender.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }
        if(course === ""){
            let notification = document.getElementById("notification");
            notification.textContent = "Please select a course.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }
        if(skills.length === 0){
            let notification = document.getElementById("notification");
            notification.textContent = "Please select at least one skill.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }
        if(address === ""){
            let notification = document.getElementById("notification");
            notification.textContent = "Please fill in the address field.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }
        if(terms === false){
            let notification = document.getElementById("notification");
            notification.textContent = "Please accept the terms and conditions.";
            notification.classList.remove("notification-success");
            notification.classList.add("notification-error");
            notification.style.display = "block";
        }


    });
}
