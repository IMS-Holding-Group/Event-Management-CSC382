// app.js — JavaScript محلي بدون مكتبات خارجية

// التحقق من صحة النماذج قبل الإرسال
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // تأكيد قبل حذف
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('هل أنت متأكد من الحذف؟ لا يمكن التراجع عن هذا الإجراء.')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // تأكيد قبل تعديل
    const editButtons = document.querySelectorAll('[data-confirm-edit]');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('هل تريد تعديل هذه الفعالية؟')) {
                e.preventDefault();
                return false;
            }
        });
    });
});

/**
 * التحقق من صحة النموذج
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // التحقق من البريد الإلكتروني
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !validateEmail(field.value)) {
            showFieldError(field, 'البريد الإلكتروني غير صحيح');
            isValid = false;
        }
    });
    
    // التحقق من عدد المقاعد
    const seatsFields = form.querySelectorAll('input[name="seats"]');
    seatsFields.forEach(field => {
        if (field.value) {
            const seats = parseInt(field.value);
            if (isNaN(seats) || seats < 1) {
                showFieldError(field, 'عدد المقاعد يجب أن يكون رقماً موجباً');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

/**
 * التحقق من حقل واحد
 */
function validateField(field) {
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'هذا الحقل مطلوب');
        return false;
    }
    
    // التحقق من الحد الأدنى
    if (field.hasAttribute('minlength')) {
        const minLength = parseInt(field.getAttribute('minlength'));
        if (value.length < minLength) {
            showFieldError(field, `يجب أن يكون طول النص ${minLength} أحرف على الأقل`);
            return false;
        }
    }
    
    // التحقق من الحد الأقصى
    if (field.hasAttribute('maxlength')) {
        const maxLength = parseInt(field.getAttribute('maxlength'));
        if (value.length > maxLength) {
            showFieldError(field, `يجب أن يكون طول النص ${maxLength} أحرف على الأكثر`);
            return false;
        }
    }
    
    clearFieldError(field);
    return true;
}

/**
 * التحقق من البريد الإلكتروني
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * عرض رسالة خطأ للحقل
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

/**
 * إزالة رسالة الخطأ من الحقل
 */
function clearFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentNode.querySelector('.error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * تحديث عدد المقاعد المتبقية
 */
function updateRemainingSeats(eventId, totalCapacity, registeredSeats) {
    const remaining = totalCapacity - registeredSeats;
    const seatsInput = document.querySelector(`input[name="seats"][data-event-id="${eventId}"]`);
    const remainingDisplay = document.querySelector(`[data-remaining-seats="${eventId}"]`);
    
    if (remainingDisplay) {
        remainingDisplay.textContent = remaining;
    }
    
    if (seatsInput) {
        const maxSeats = Math.max(1, remaining);
        seatsInput.setAttribute('max', maxSeats);
        if (parseInt(seatsInput.value) > maxSeats) {
            seatsInput.value = maxSeats;
        }
    }
}

