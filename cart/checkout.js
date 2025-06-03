function validateForm() {
    const addressType = document.querySelector('input[name="address_type"]:checked').value;

    if (addressType === 'new') {
        const street = document.querySelector('input[name="street"]').value.trim();
        const postal = document.querySelector('input[name="postal_code"]').value.trim();
        const country = document.querySelector('input[name="country"]').value.trim();

        if (!street || !postal || !country) {
            alert("Please fill in all fields of the new address..");
            return false;
        }
    } else {
        const savedAddress = document.querySelector('select[name="saved_address_id"]').value;
        if (!savedAddress) {
            alert("Please select a saved address.");
            return false;
        }
    }

    const paymentMethod = document.getElementById('payment-method').value;
    if (!paymentMethod || paymentMethod === "") {
        alert("Please select a payment method.");
        return false;
    }

    return true;
}


    let currentStep = 1;

    function showStep(step) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        document.getElementById('progress-bar').style.width = step === 2 ? '100%' : '50%';
        document.getElementById('progress-text').innerText = 'Step ' + step + ' of 2';
        document.getElementById('next-btn').classList.toggle('hidden', step === 2);
        document.querySelector('button[type="submit"]').classList.toggle('hidden', step !== 2);

    }

    function nextStep() {
        if (currentStep < 2) currentStep++;
        showStep(currentStep);
    }

    function prevStep() {
        if (currentStep > 1) currentStep--;
        showStep(currentStep);
    }

    document.querySelectorAll('input[name="address_type"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const isNew = document.querySelector('input[name="address_type"]:checked').value === 'new';
            document.getElementById('new-address-fields').classList.toggle('hidden', !isNew);
            document.getElementById('saved-address-fields').classList.toggle('hidden', isNew);
        });
    });

    document.getElementById('payment-method').addEventListener('change', () => {
        const val = document.getElementById('payment-method').value;
        document.getElementById('card-fields').classList.toggle('hidden', val !== 'card');
        document.getElementById('paypal-fields').classList.toggle('hidden', val !== 'paypal');
    });

    // Инициализация
    showStep(currentStep);
    function checkAddressValidity() {
    const addressType = document.querySelector('input[name="address_type"]:checked').value;
    const nextBtn = document.getElementById('next-btn');

    if (addressType === 'new') {
        const street = document.querySelector('input[name="street"]').value.trim();
        const postal = document.querySelector('input[name="postal_code"]').value.trim();
        const country = document.querySelector('input[name="country"]').value.trim();

        nextBtn.disabled = !(street && postal && country);
    } else {
        const savedAddress = document.querySelector('select[name="saved_address_id"]').value;
        nextBtn.disabled = !savedAddress;
    }
}
checkAddressValidity();

// слушаем смену radio-кнопок
document.querySelectorAll('input[name="address_type"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const isNew = radio.value === 'new';
        document.getElementById('new-address-fields').classList.toggle('hidden', !isNew);
        document.getElementById('saved-address-fields').classList.toggle('hidden', isNew);
        checkAddressValidity();
    });
});

// слушаем ввод в новые адреса
['street', 'postal_code', 'country'].forEach(name => {
    const input = document.querySelector(`input[name="${name}"]`);
    if (input) {
        input.addEventListener('input', checkAddressValidity);
    };
});

// слушаем выбор сохранённого адреса
const savedSelect = document.querySelector('select[name="saved_address_id"]');
if (savedSelect) {
    savedSelect.addEventListener('change', checkAddressValidity);
}

document.getElementById('payment-method').addEventListener('change', () => {
    const val = document.getElementById('payment-method').value;

    const cardFields = document.getElementById('card-fields');
    const paypalFields = document.getElementById('paypal-fields');

    cardFields.classList.toggle('hidden', val !== 'card');
    paypalFields.classList.toggle('hidden', val !== 'paypal');

    // Обновляем required для полей
    document.querySelector('[name="card_number"]').required = (val === 'card');
    document.querySelector('[name="card_expiry"]').required = (val === 'card');
    document.querySelector('[name="card_cvc"]').required = (val === 'card');
    document.querySelector('[name="paypal_email"]').required = (val === 'paypal');
    document.querySelector('[name="paypal_owner"]').required = (val === 'paypal');
});
document.addEventListener('DOMContentLoaded', () => {
    const cardNumberInput = document.getElementById('card_number');
    const expiryInput = document.getElementById('card_expiry');


    cardNumberInput.addEventListener('input', function (e) {
        let value = this.value.replace(/\D/g, '').substring(0, 16);
        let formatted = value.match(/.{1,4}/g);
        this.value = formatted ? formatted.join(' ') : '';
    });


    expiryInput.addEventListener('input', function () {
        let value = this.value.replace(/\D/g, '').substring(0, 4);
        if (value.length >= 3) {
            this.value = value.substring(0, 2) + '/' + value.substring(2);
        } else {
            this.value = value;
        }
    });


    expiryInput.addEventListener('blur', function () {
        const [month, year] = this.value.split('/');
        if (parseInt(month) > 12) {
            alert('A month cannot be more than 12.');
            this.focus();
        }
    });
});

