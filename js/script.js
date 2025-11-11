// وظائف عامة للنظام
document.addEventListener('DOMContentLoaded', function() {
    // إدارة التبويبات
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            openTab(tabName);
        });
    });
    
    // تأكيد الحذف
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من أنك تريد الحذف؟')) {
                e.preventDefault();
            }
        });
    });
    
    // تحديث الوقت الحقيقي للإشعارات
    updateNotificationTimes();
});

function openTab(tabName) {
    // إخفاء جميع محتويات التبويب
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
    });
    
    // إزالة النشاط من جميع أزرار التبويب
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // إظهار محتوى التبويب المحدد
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

function updateNotificationTimes() {
    const timeElements = document.querySelectorAll('.notification-time');
    timeElements.forEach(element => {
        // هنا يمكن إضافة منطق لحساب الوقت المنقضي
    });
}

// وظائف خاصة بالرسائل
function sendMessage(receiverId, internshipId = null) {
    const messageText = document.getElementById('message-text').value;
    
    if (messageText.trim() === '') {
        alert('يرجى كتابة رسالة');
        return;
    }
    
    // إرسال الرسالة عبر AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../includes/message-handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                document.getElementById('message-text').value = '';
                loadMessages(); // إعادة تحميل المحادثة
            } else {
                alert('حدث خطأ في إرسال الرسالة: ' + response.message);
            }
        }
    };
    
    const params = `action=send&receiver_id=${receiverId}&message=${encodeURIComponent(messageText)}${internshipId ? '&internship_id=' + internshipId : ''}`;
    xhr.send(params);
}

function loadMessages(conversationId = null) {
    // تحميل الرسائل عبر AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `../includes/message-handler.php?action=load${conversationId ? '&conversation_id=' + conversationId : ''}`, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('messages-container').innerHTML = xhr.responseText;
            scrollToBottom();
        }
    };
    
    xhr.send();
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}