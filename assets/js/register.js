// إظهار/إخفاء كلمة المرور
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const icon = passwordField.nextElementSibling.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// التحقق من تطابق كلمة المرور
function validatePassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const messageDiv = document.getElementById('message');
    
    if (password !== confirmPassword) {
        showMessage('كلمة المرور غير متطابقة', 'error');
        return false;
    }
    
    if (password.length < 6) {
        showMessage('كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'error');
        return false;
    }
    
    return true;
}

// معالجة إنشاء الحساب
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validatePassword()) {
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'register');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // عرض حالة التحميل
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري إنشاء الحساب...';
    submitBtn.disabled = true;
    
    // إرسال الطلب
    fetch('controllers/AuthController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showMessage(data.message, 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showMessage('حدث خطأ في الاتصال بالخادم', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// عرض الرسائل
function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';
    
    // إخفاء الرسالة تلقائياً بعد 5 ثواني
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// التحقق من صحة النموذج أثناء الكتابة
document.getElementById('registerForm').addEventListener('input', function() {
    const requiredFields = ['first_name', 'last_name', 'username', 'email', 'user_type', 'password', 'confirm_password'];
    const isFormValid = requiredFields.every(field => {
        const element = document.getElementById(field);
        return element && element.value.trim() !== '';
    });
    
    const agreeTerms = document.querySelector('input[name="agree_terms"]').checked;
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = !(isFormValid && agreeTerms);
});