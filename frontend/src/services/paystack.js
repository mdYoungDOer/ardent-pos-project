import api from './api';

class PaystackService {
  constructor() {
    this.publicKey = null;
    this.initialized = false;
  }

  async initialize() {
    if (this.initialized) return;

    try {
      // Get public key from backend
      const response = await api.get('/paystack/config');
      this.publicKey = response.data.public_key;
      
      // Load Paystack inline script
      await this.loadPaystackScript();
      this.initialized = true;
    } catch (error) {
      console.error('Failed to initialize Paystack:', error);
      throw new Error('Payment system initialization failed');
    }
  }

  loadPaystackScript() {
    return new Promise((resolve, reject) => {
      if (window.PaystackPop) {
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://js.paystack.co/v1/inline.js';
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  async initializePayment(paymentData) {
    await this.initialize();

    return new Promise((resolve, reject) => {
      const handler = window.PaystackPop.setup({
        key: this.publicKey,
        email: paymentData.email,
        amount: paymentData.amount * 100, // Convert to kobo
        currency: paymentData.currency || 'GHS',
        ref: paymentData.reference,
        metadata: paymentData.metadata || {},
        callback: (response) => {
          resolve(response);
        },
        onClose: () => {
          reject(new Error('Payment cancelled by user'));
        }
      });

      handler.openIframe();
    });
  }

  async verifyPayment(reference) {
    try {
      const response = await api.get(`/paystack/verify/${reference}`);
      return response.data;
    } catch (error) {
      throw new Error('Payment verification failed');
    }
  }

  async initializeSubscription(subscriptionData) {
    try {
      const response = await api.post('/subscription/upgrade', subscriptionData);
      
      if (response.data.payment_data) {
        const paymentResult = await this.initializePayment(response.data.payment_data);
        
        // Verify the payment
        if (paymentResult.status === 'success') {
          await this.verifyPayment(paymentResult.reference);
        }
        
        return paymentResult;
      }
      
      return response.data;
    } catch (error) {
      throw new Error('Subscription initialization failed');
    }
  }

  generateReference(prefix = 'txn') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  formatAmount(amount) {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  }
}

export default new PaystackService();
