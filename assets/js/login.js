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

// معالجة تسجيل الدخول
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'login');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // عرض حالة التحميل
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تسجيل الدخول...';
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
                window.location.href = 'dashboard.php';
            }, 1500);
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

// التحقق من صحة النموذج
document.getElementById('loginForm').addEventListener('input', function() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = !(username && password);
});