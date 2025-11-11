// معرف المستخدم الحالي (من جلسة PHP أو متغير عام)
const CURRENT_USER_ID = 123; // غيّر هذا حسب قيمة المستخدم الحالي

// تحميل الرسائل
function loadMessages() {
  fetch('communication/includes/message-handler.php?action=load')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const container = document.querySelector('#messages');
        container.innerHTML = ''; // تفريغ الرسائل القديمة

        data.messages.forEach(msg => {
          const div = document.createElement('div');
          div.classList.add('message', msg.sender_id == CURRENT_USER_ID ? 'sent' : 'received');

          div.innerHTML = `
            <div class="message-content">
              <p>${msg.message_body}</p>
              <span class="message-time">${msg.sent_date}</span>
              <span class="message-user">${msg.first_name} ${msg.last_name}</span>
            </div>
          `;

          container.appendChild(div);
        });
      } else {
        console.error("Error loading messages:", data.error);
      }
    })
    .catch(err => console.error("Fetch error:", err));
}

// إرسال رسالة جديدة
function sendMessage(receiverId, internshipId, messageText) {
  const formData = new FormData();
  formData.append('action', 'send');
  formData.append('receiver_id', receiverId);
  formData.append('internship_id', internshipId);
  formData.append('message', messageText);

  fetch('communication/includes/message-handler.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log("Message sent successfully:", data);
        loadMessages(); // إعادة تحميل الرسائل بعد الإرسال
      } else {
        console.error("Error sending message:", data.error);
      }
    })
    .catch(err => console.error("Fetch error:", err));
}
