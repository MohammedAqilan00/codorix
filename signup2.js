document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".register-form");
    const usernameInput = form.querySelector("input[name='username']");
    const emailInput = form.querySelector("input[name='email']");
    const passwordInput = form.querySelector("input[name='password']");
    const confirmInput = form.querySelector("input[name='confirm_password']");
    const passwordConditions = {
        length: document.getElementById("condition-length"),
        uppercase: document.getElementById("condition-uppercase"),
        number: document.getElementById("condition-number"),
        symbol: document.getElementById("condition-symbol")
    };
    const daySelect = document.getElementById("day");
    const monthSelect = document.getElementById("month");
    const yearSelect = document.getElementById("year");

    // 🔧 دالة لعرض الرسائل أسفل الحقل
    function showFieldMessage(input, message, isValid) {
        let group = input.closest(".input-group");
        let msg = group.querySelector(".input-error");

        if (!msg) {
            msg = document.createElement("div");
            msg.className = "input-error";
            group.appendChild(msg);
        }

        msg.textContent = message;
        group.classList.toggle("error", !isValid);
        group.classList.toggle("valid", isValid);
    }

    function validatePassword(pwd) {
        const isLength = pwd.length >= 8;
        const isUpper = /[A-Z]/.test(pwd);
        const isNumber = /[0-9]/.test(pwd);
        const isSymbol = /[!@#\$%\^&\*]/.test(pwd);

        passwordConditions.length.classList.toggle("valid", isLength);
        passwordConditions.uppercase.classList.toggle("valid", isUpper);
        passwordConditions.number.classList.toggle("valid", isNumber);
        passwordConditions.symbol.classList.toggle("valid", isSymbol);

        return isLength && isUpper && isNumber && isSymbol;
    }

    passwordInput.addEventListener("input", function () {
        validatePassword(passwordInput.value);
    });

    usernameInput.addEventListener("blur", function () {
        const username = usernameInput.value.trim();

        if (username.length < 5) {
            showFieldMessage(usernameInput, "اسم المستخدم يجب أن لا يقل عن 5 أحرف", false);
            return;
        }
/*
        fetch("check_username.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "username=" + encodeURIComponent(username)
            
        })
        .then(res => res.json())
        .then(data => {
            showFieldMessage(usernameInput, data.message, data.valid);
            
        })*/
       /* *//*.then(data => {
            if (!data.message) data.message = data.valid ? "✔" : "اسم المستخدم غير متاح";
            showFieldMessage(usernameInput, data.message, data.valid);
        })
        .catch(() => {
            showFieldMessage(usernameInput, "فشل الاتصال بالخادم", false);
        });*/
        
    });

    emailInput.addEventListener("blur", function () {
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(email)) {
            showFieldMessage(emailInput, "صيغة البريد الإلكتروني غير صحيحة", false);
        } else {
            showFieldMessage(emailInput, "✔", true);
        }
    });

    confirmInput.addEventListener("blur", function () {
        const pwd = passwordInput.value;
        const confirmPwd = confirmInput.value;

        if (pwd !== confirmPwd) {
            showFieldMessage(confirmInput, "كلمتا المرور غير متطابقتين", false);
        } else {
            showFieldMessage(confirmInput, "✔", true);
        }
    });

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        let isValid = true;

        const pwd = passwordInput.value;
        const confirmPwd = confirmInput.value;
        const year = parseInt(form.querySelector("select[name='year']").value);
        const currentYear = new Date().getFullYear();

        if (!validatePassword(pwd)) {
            showFieldMessage(passwordInput, "كلمة المرور لا تحقق الشروط", false);
            isValid = false;
        } else {
            showFieldMessage(passwordInput, "✔", true);
        }

        if (pwd !== confirmPwd) {
            showFieldMessage(confirmInput, "كلمتا المرور غير متطابقتين", false);
            isValid = false;
        } else {
            showFieldMessage(confirmInput, "✔", true);
        }

        if (currentYear - year < 10) {
            const yearSelect = form.querySelector("select[name='year']");
            showFieldMessage(yearSelect, "العمر يجب أن لا يقل عن 10 سنوات", false);
            isValid = false;
        }

        if (!isValid) return;

        const formData = new FormData(form);

        fetch("register.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ تم إنشاء الحساب بنجاح");
                window.location.href = "home.php";
            } else if (data.errors) {
                for (let field in data.errors) {
                    const input = form.querySelector(`[name='${field}']`);
                    if (input) showFieldMessage(input, data.errors[field], false);
                }
            } else {
                alert("❌ حدث خطأ غير متوقع.");
            }
        })
        .catch(error => {
            console.error(error);
            alert("❌ فشل في الاتصال بالخادم.");
        });
    });

    // في ملف JS أو داخل <script> في أسفل الصفحة:


    // تعبئة السنوات من 1900 إلى السنة الحالية - 10 سنوات (يعني من عمر 10+ فقط)
    const currentYear = new Date().getFullYear();
    for (let y = currentYear - 10; y >= 1900; y--) {
        const option = document.createElement("option");
        option.value = y;
        option.text = y;
        yearSelect.appendChild(option);
    }

    // تعبئة الشهور
    const months = [
        "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو",
        "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"
    ];
    months.forEach((name, index) => {
        const option = document.createElement("option");
        option.value = index + 1;
        option.text = name;
        monthSelect.appendChild(option);
    });


    

    // دالة لحساب عدد الأيام في شهر معين وسنة معينة
    function getDaysInMonth(month, year) {
        if ([1, 3, 5, 7, 8, 10, 12].includes(month)) return 31;
        if ([4, 6, 9, 11].includes(month)) return 30;
        if (month === 2) {
            // تحقق من السنة الكبيسة
            if ((year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0)) {
                return 29;
            } else {
                return 28;
            }
        }
        return 0;
    }

    // دالة لتعبئة الأيام حسب الشهر والسنة
    function populateDays() {
        const selectedMonth = parseInt(monthSelect.value);
        const selectedYear = parseInt(yearSelect.value);

        // لا تفعل شيئًا لو لم يتم اختيار الشهر أو السنة بعد
        if (!selectedMonth || !selectedYear) return;

        const daysInMonth = getDaysInMonth(selectedMonth, selectedYear);

        // تفريغ الخيارات القديمة
        daySelect.innerHTML = "";

        for (let i = 1; i <= daysInMonth; i++) {
            const option = document.createElement("option");
            option.value = i;
            option.text = i;
            daySelect.appendChild(option);
        }
    }

    // استدعاء populateDays عند تغيير الشهر أو السنة
    monthSelect.addEventListener("change", populateDays);
    yearSelect.addEventListener("change", populateDays);
});



