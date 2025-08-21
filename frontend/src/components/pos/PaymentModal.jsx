import React, { useState } from 'react';
import { toast } from 'react-hot-toast';
import { XMarkIcon, CreditCardIcon, BanknotesIcon } from '@heroicons/react/24/outline';
import LoadingSpinner from '../ui/LoadingSpinner';
import paystackService from '../../services/paystack';

const PaymentModal = ({ isOpen, onClose, saleData, onPaymentSuccess }) => {
  const [paymentMethod, setPaymentMethod] = useState('card');
  const [processing, setProcessing] = useState(false);
  const [cashReceived, setCashReceived] = useState('');

  const paymentMethods = [
    { id: 'card', name: 'Card Payment', icon: CreditCardIcon, description: 'Pay with debit/credit card' },
    { id: 'cash', name: 'Cash Payment', icon: BanknotesIcon, description: 'Cash transaction' },
    { id: 'mobile_money', name: 'Mobile Money', icon: CreditCardIcon, description: 'MTN/Vodafone/AirtelTigo' }
  ];

  const handlePayment = async () => {
    if (processing) return;

    if (paymentMethod === 'cash') {
      const received = parseFloat(cashReceived);
      if (!received || received < saleData.total) {
        toast.error('Cash received must be at least the total amount');
        return;
      }
      
      // Process cash payment
      const change = received - saleData.total;
      onPaymentSuccess({
        method: 'cash',
        amount: saleData.total,
        received: received,
        change: change
      });
      return;
    }

    // Process card/mobile money payment
    setProcessing(true);
    
    try {
      const paymentData = {
        email: saleData.customer?.email || 'customer@example.com',
        amount: saleData.total,
        currency: 'GHS',
        reference: paystackService.generateReference('sale'),
        metadata: {
          sale_id: saleData.id,
          customer_id: saleData.customer?.id,
          payment_method: paymentMethod
        }
      };

      const result = await paystackService.initializePayment(paymentData);
      
      if (result.status === 'success') {
        // Verify payment
        const verification = await paystackService.verifyPayment(result.reference);
        
        if (verification.status && verification.data.status === 'success') {
          onPaymentSuccess({
            method: paymentMethod,
            amount: saleData.total,
            reference: result.reference,
            gateway_response: verification.data
          });
        } else {
          toast.error('Payment verification failed');
        }
      }
    } catch (error) {
      console.error('Payment error:', error);
      toast.error(error.message || 'Payment failed');
    } finally {
      setProcessing(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg max-w-md w-full p-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-semibold text-gray-900">Process Payment</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon className="h-6 w-6" />
          </button>
        </div>

        {/* Sale Summary */}
        <div className="bg-gray-50 rounded-lg p-4 mb-6">
          <div className="flex justify-between items-center mb-2">
            <span className="text-gray-600">Subtotal:</span>
            <span className="font-medium">{formatCurrency(saleData.subtotal)}</span>
          </div>
          {saleData.tax > 0 && (
            <div className="flex justify-between items-center mb-2">
              <span className="text-gray-600">Tax:</span>
              <span className="font-medium">{formatCurrency(saleData.tax)}</span>
            </div>
          )}
          {saleData.discount > 0 && (
            <div className="flex justify-between items-center mb-2">
              <span className="text-gray-600">Discount:</span>
              <span className="font-medium text-green-600">-{formatCurrency(saleData.discount)}</span>
            </div>
          )}
          <div className="border-t pt-2 mt-2">
            <div className="flex justify-between items-center">
              <span className="text-lg font-semibold">Total:</span>
              <span className="text-lg font-bold text-primary">{formatCurrency(saleData.total)}</span>
            </div>
          </div>
        </div>

        {/* Payment Methods */}
        <div className="mb-6">
          <h3 className="text-sm font-medium text-gray-700 mb-3">Select Payment Method</h3>
          <div className="space-y-3">
            {paymentMethods.map((method) => {
              const Icon = method.icon;
              return (
                <label
                  key={method.id}
                  className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                    paymentMethod === method.id
                      ? 'border-primary bg-primary-light'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="paymentMethod"
                    value={method.id}
                    checked={paymentMethod === method.id}
                    onChange={(e) => setPaymentMethod(e.target.value)}
                    className="sr-only"
                  />
                  <Icon className="h-5 w-5 text-gray-600 mr-3" />
                  <div>
                    <div className="font-medium text-gray-900">{method.name}</div>
                    <div className="text-sm text-gray-600">{method.description}</div>
                  </div>
                </label>
              );
            })}
          </div>
        </div>

        {/* Cash Payment Input */}
        {paymentMethod === 'cash' && (
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Cash Received
            </label>
            <input
              type="number"
              step="0.01"
              min={saleData.total}
              value={cashReceived}
              onChange={(e) => setCashReceived(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder={`Minimum: ${formatCurrency(saleData.total)}`}
            />
            {cashReceived && parseFloat(cashReceived) >= saleData.total && (
              <div className="mt-2 text-sm text-green-600">
                Change: {formatCurrency(parseFloat(cashReceived) - saleData.total)}
              </div>
            )}
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex gap-3">
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handlePayment}
            disabled={processing || (paymentMethod === 'cash' && (!cashReceived || parseFloat(cashReceived) < saleData.total))}
            className="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {processing ? (
              <LoadingSpinner size="sm" />
            ) : (
              `Process Payment`
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default PaymentModal;
