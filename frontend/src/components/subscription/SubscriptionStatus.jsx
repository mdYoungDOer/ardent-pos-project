import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { 
  CreditCardIcon, 
  CalendarIcon, 
  ExclamationTriangleIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/react/24/outline';
import api from '../../services/api';
import LoadingSpinner from '../ui/LoadingSpinner';

const SubscriptionStatus = () => {
  const queryClient = useQueryClient();
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelReason, setCancelReason] = useState('');

  const { data: subscription, isLoading, error } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => {
      const response = await api.get('/subscription');
      return response.data;
    }
  });

  const cancelMutation = useMutation({
    mutationFn: async (reason) => {
      const response = await api.post('/subscription/cancel', { reason });
      return response.data;
    },
    onSuccess: () => {
      toast.success('Subscription cancelled successfully');
      queryClient.invalidateQueries(['subscription']);
      setShowCancelModal(false);
      setCancelReason('');
    },
    onError: (error) => {
      toast.error(error.response?.data?.error || 'Failed to cancel subscription');
    }
  });

  const handleCancelSubscription = () => {
    if (!cancelReason.trim()) {
      toast.error('Please provide a reason for cancellation');
      return;
    }
    cancelMutation.mutate(cancelReason);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active':
        return 'text-green-600 bg-green-100';
      case 'past_due':
        return 'text-yellow-600 bg-yellow-100';
      case 'cancelled':
        return 'text-red-600 bg-red-100';
      case 'pending':
        return 'text-blue-600 bg-blue-100';
      default:
        return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'active':
        return <CheckCircleIcon className="h-5 w-5" />;
      case 'past_due':
        return <ExclamationTriangleIcon className="h-5 w-5" />;
      case 'cancelled':
        return <XCircleIcon className="h-5 w-5" />;
      default:
        return <CreditCardIcon className="h-5 w-5" />;
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const getPlanDisplayName = (planName) => {
    return planName.charAt(0).toUpperCase() + planName.slice(1);
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6">
        <div className="flex items-center">
          <ExclamationTriangleIcon className="h-6 w-6 text-red-600 mr-3" />
          <div>
            <h3 className="text-lg font-medium text-red-800">Error Loading Subscription</h3>
            <p className="text-red-600 mt-1">
              {error.response?.data?.error || 'Failed to load subscription information'}
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Current Plan Card */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900">Current Subscription</h2>
          <div className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(subscription.status)}`}>
            {getStatusIcon(subscription.status)}
            <span className="ml-2 capitalize">{subscription.status}</span>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <h3 className="text-sm font-medium text-gray-500 mb-1">Plan</h3>
            <p className="text-lg font-semibold text-gray-900">
              {getPlanDisplayName(subscription.plan_name)}
            </p>
            {subscription.billing_cycle && (
              <p className="text-sm text-gray-600 capitalize">
                {subscription.billing_cycle} billing
              </p>
            )}
          </div>

          {subscription.amount && (
            <div>
              <h3 className="text-sm font-medium text-gray-500 mb-1">Amount</h3>
              <p className="text-lg font-semibold text-gray-900">
                {formatCurrency(subscription.amount)}
              </p>
              <p className="text-sm text-gray-600">
                per {subscription.billing_cycle === 'yearly' ? 'year' : 'month'}
              </p>
            </div>
          )}

          <div>
            <h3 className="text-sm font-medium text-gray-500 mb-1">
              {subscription.status === 'active' ? 'Next Billing' : 'Ends'}
            </h3>
            <div className="flex items-center">
              <CalendarIcon className="h-4 w-4 text-gray-400 mr-2" />
              <p className="text-lg font-semibold text-gray-900">
                {formatDate(subscription.ends_at)}
              </p>
            </div>
          </div>
        </div>

        {subscription.status === 'past_due' && (
          <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div className="flex items-start">
              <ExclamationTriangleIcon className="h-5 w-5 text-yellow-600 mt-0.5 mr-3" />
              <div>
                <h4 className="text-sm font-medium text-yellow-800">Payment Past Due</h4>
                <p className="text-sm text-yellow-700 mt-1">
                  Your last payment failed. Please update your payment method to continue using the service.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Usage Statistics */}
      {subscription.usage && (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Usage This Month</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="text-center">
              <div className="text-3xl font-bold text-primary mb-2">
                {subscription.usage.sales_this_month}
              </div>
              <div className="text-sm text-gray-600">Sales Transactions</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-bold text-primary mb-2">
                {subscription.usage.total_products}
              </div>
              <div className="text-sm text-gray-600">Products</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-bold text-primary mb-2">
                {subscription.usage.total_users}
              </div>
              <div className="text-sm text-gray-600">Team Members</div>
            </div>
          </div>
        </div>
      )}

      {/* Actions */}
      {subscription.status === 'active' && (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Manage Subscription</h2>
          <div className="flex flex-col sm:flex-row gap-4">
            <button
              onClick={() => setShowCancelModal(true)}
              className="px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition-colors"
            >
              Cancel Subscription
            </button>
          </div>
        </div>
      )}

      {/* Cancel Modal */}
      {showCancelModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Cancel Subscription
            </h3>
            <p className="text-gray-600 mb-4">
              Are you sure you want to cancel your subscription? You'll continue to have access until {formatDate(subscription.ends_at)}.
            </p>
            
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Reason for cancellation (optional)
              </label>
              <textarea
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                rows={3}
                placeholder="Help us improve by telling us why you're cancelling..."
              />
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => setShowCancelModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Keep Subscription
              </button>
              <button
                onClick={handleCancelSubscription}
                disabled={cancelMutation.isLoading}
                className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
              >
                {cancelMutation.isLoading ? <LoadingSpinner size="sm" /> : 'Cancel Subscription'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SubscriptionStatus;
