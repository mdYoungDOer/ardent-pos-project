import React, { useState, useEffect } from 'react';
import { FiPlus, FiEdit, FiTrash2, FiEye, FiCheck, FiX } from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const SuperAdminSubscriptionPlansPage = () => {
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingPlan, setEditingPlan] = useState(null);
  const [formData, setFormData] = useState({
    plan_id: '',
    name: '',
    description: '',
    monthly_price: '',
    yearly_price: '',
    features: [],
    limits: {
      locations: '',
      users: '',
      products: '',
      customers: '',
      transactions_per_month: '',
      storage_gb: '',
      api_calls_per_month: '',
      backup_retention_days: ''
    },
    is_active: true,
    is_popular: false
  });

  useEffect(() => {
    loadPlans();
  }, []);

  const loadPlans = async () => {
    try {
      setLoading(true);
      const response = await superAdminAPI.getSubscriptionPlans();
      if (response.data?.success) {
        setPlans(response.data.data);
      }
    } catch (error) {
      console.error('Error loading plans:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingPlan) {
        await superAdminAPI.updateSubscriptionPlan(editingPlan.id, formData);
      } else {
        await superAdminAPI.createSubscriptionPlan(formData);
      }
      setShowModal(false);
      setEditingPlan(null);
      resetForm();
      loadPlans();
    } catch (error) {
      console.error('Error saving plan:', error);
    }
  };

  const handleEdit = (plan) => {
    setEditingPlan(plan);
    setFormData({
      plan_id: plan.plan_id,
      name: plan.name,
      description: plan.description,
      monthly_price: plan.monthly_price,
      yearly_price: plan.yearly_price,
      features: plan.features || [],
      limits: plan.limits || {},
      is_active: plan.is_active,
      is_popular: plan.is_popular
    });
    setShowModal(true);
  };

  const handleDelete = async (planId) => {
    if (window.confirm('Are you sure you want to delete this plan?')) {
      try {
        await superAdminAPI.deleteSubscriptionPlan(planId);
        loadPlans();
      } catch (error) {
        console.error('Error deleting plan:', error);
      }
    }
  };

  const resetForm = () => {
    setFormData({
      plan_id: '',
      name: '',
      description: '',
      monthly_price: '',
      yearly_price: '',
      features: [],
      limits: {
        locations: '',
        users: '',
        products: '',
        customers: '',
        transactions_per_month: '',
        storage_gb: '',
        api_calls_per_month: '',
        backup_retention_days: ''
      },
      is_active: true,
      is_popular: false
    });
  };

  const addFeature = () => {
    setFormData(prev => ({
      ...prev,
      features: [...prev.features, '']
    }));
  };

  const updateFeature = (index, value) => {
    setFormData(prev => ({
      ...prev,
      features: prev.features.map((feature, i) => i === index ? value : feature)
    }));
  };

  const removeFeature = (index) => {
    setFormData(prev => ({
      ...prev,
      features: prev.features.filter((_, i) => i !== index)
    }));
  };

  const updateLimit = (key, value) => {
    setFormData(prev => ({
      ...prev,
      limits: {
        ...prev.limits,
        [key]: value
      }
    }));
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-8 flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Subscription Plans</h1>
          <p className="mt-2 text-gray-600">Manage subscription plans and pricing</p>
        </div>
        <button
          onClick={() => setShowModal(true)}
          className="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors flex items-center"
        >
          <FiPlus className="h-4 w-4 mr-2" />
          Add Plan
        </button>
      </div>

      {/* Plans Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {plans.map((plan) => (
          <div key={plan.id} className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
            {plan.is_popular && (
              <div className="bg-primary text-white text-center py-2 text-sm font-medium">
                Most Popular
              </div>
            )}
            <div className="p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h3 className="text-xl font-bold text-gray-900">{plan.name}</h3>
                  <p className="text-gray-600 text-sm mt-1">{plan.description}</p>
                </div>
                <div className="flex space-x-2">
                  <button
                    onClick={() => handleEdit(plan)}
                    className="text-gray-400 hover:text-primary transition-colors"
                  >
                    <FiEdit className="h-4 w-4" />
                  </button>
                  <button
                    onClick={() => handleDelete(plan.id)}
                    className="text-gray-400 hover:text-red-500 transition-colors"
                  >
                    <FiTrash2 className="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div className="mb-4">
                <div className="text-2xl font-bold text-gray-900">
                  ₵{plan.monthly_price}
                  <span className="text-sm font-normal text-gray-500">/month</span>
                </div>
                <div className="text-sm text-gray-500">
                  ₵{plan.yearly_price}/year (Save ₵{plan.monthly_price * 12 - plan.yearly_price})
                </div>
              </div>

              <div className="mb-4">
                <h4 className="font-medium text-gray-900 mb-2">Features:</h4>
                <ul className="space-y-1">
                  {plan.features?.slice(0, 3).map((feature, index) => (
                    <li key={index} className="flex items-center text-sm text-gray-600">
                      <FiCheck className="h-3 w-3 text-green-500 mr-2" />
                      {feature}
                    </li>
                  ))}
                  {plan.features?.length > 3 && (
                    <li className="text-sm text-gray-500">
                      +{plan.features.length - 3} more features
                    </li>
                  )}
                </ul>
              </div>

              <div className="flex items-center justify-between">
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                  plan.is_active 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'
                }`}>
                  {plan.is_active ? 'Active' : 'Inactive'}
                </span>
                <span className="text-xs text-gray-500">
                  Plan ID: {plan.plan_id}
                </span>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-xl font-bold">
                {editingPlan ? 'Edit Plan' : 'Add New Plan'}
              </h2>
              <button
                onClick={() => {
                  setShowModal(false);
                  setEditingPlan(null);
                  resetForm();
                }}
                className="text-gray-400 hover:text-gray-600"
              >
                <FiX className="h-6 w-6" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Plan ID
                  </label>
                  <input
                    type="text"
                    value={formData.plan_id}
                    onChange={(e) => setFormData(prev => ({ ...prev, plan_id: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="e.g., starter"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Plan Name
                  </label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="e.g., Starter"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Description
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                  rows="3"
                  placeholder="Plan description..."
                  required
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Monthly Price (₵)
                  </label>
                  <input
                    type="number"
                    value={formData.monthly_price}
                    onChange={(e) => setFormData(prev => ({ ...prev, monthly_price: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="120.00"
                    step="0.01"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Yearly Price (₵)
                  </label>
                  <input
                    type="number"
                    value={formData.yearly_price}
                    onChange={(e) => setFormData(prev => ({ ...prev, yearly_price: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="1200.00"
                    step="0.01"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Features
                </label>
                <div className="space-y-2">
                  {formData.features.map((feature, index) => (
                    <div key={index} className="flex items-center space-x-2">
                      <input
                        type="text"
                        value={feature}
                        onChange={(e) => updateFeature(index, e.target.value)}
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Feature description"
                      />
                      <button
                        type="button"
                        onClick={() => removeFeature(index)}
                        className="text-red-500 hover:text-red-700"
                      >
                        <FiX className="h-4 w-4" />
                      </button>
                    </div>
                  ))}
                  <button
                    type="button"
                    onClick={addFeature}
                    className="text-primary hover:text-primary-dark text-sm"
                  >
                    + Add Feature
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Limits
                </label>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  {Object.keys(formData.limits).map((key) => (
                    <div key={key}>
                      <label className="block text-xs font-medium text-gray-600 mb-1">
                        {key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                      </label>
                      <input
                        type="number"
                        value={formData.limits[key]}
                        onChange={(e) => updateLimit(key, e.target.value)}
                        className="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="-1 for unlimited"
                      />
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex items-center space-x-6">
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    checked={formData.is_active}
                    onChange={(e) => setFormData(prev => ({ ...prev, is_active: e.target.checked }))}
                    className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                  />
                  <span className="ml-2 text-sm text-gray-700">Active</span>
                </label>
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    checked={formData.is_popular}
                    onChange={(e) => setFormData(prev => ({ ...prev, is_popular: e.target.checked }))}
                    className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                  />
                  <span className="ml-2 text-sm text-gray-700">Popular Plan</span>
                </label>
              </div>

              <div className="flex justify-end space-x-4">
                <button
                  type="button"
                  onClick={() => {
                    setShowModal(false);
                    setEditingPlan(null);
                    resetForm();
                  }}
                  className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark"
                >
                  {editingPlan ? 'Update Plan' : 'Create Plan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default SuperAdminSubscriptionPlansPage;
