import React, { useState, useEffect } from 'react';
import {
  FiPlus, FiEdit, FiTrash, FiSearch, FiPercent, FiAlertCircle,
  FiCalendar, FiDollarSign, FiTag, FiMapPin, FiPackage, FiFilter,
  FiEye, FiEyeOff, FiClock, FiTrendingUp, FiUsers, FiGlobe, FiRotateCw
} from 'react-icons/fi';
import { discountsAPI, categoriesAPI, productsAPI, locationsAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

const DiscountsPage = () => {
  const { user } = useAuth();
  const [discounts, setDiscounts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [products, setProducts] = useState([]);
  const [locations, setLocations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [filterType, setFilterType] = useState('all');
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingDiscount, setEditingDiscount] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    type: 'percentage',
    value: '',
    scope: 'all_products',
    scope_ids: [],
    min_amount: '',
    max_discount: '',
    start_date: '',
    end_date: '',
    usage_limit: '',
    status: 'active'
  });

  // Check if user has permission to manage discounts
  const canManageDiscounts = user && ['admin', 'manager'].includes(user.role);

  useEffect(() => {
    if (canManageDiscounts) {
      fetchDiscounts();
      fetchCategories();
      fetchProducts();
      fetchLocations();
    } else if (user) {
      // User is loaded but doesn't have permissions, set loading to false
      setLoading(false);
    }
  }, [canManageDiscounts, user]);

  const fetchDiscounts = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await discountsAPI.getAll();
      if (response.data.success) {
        setDiscounts(response.data.data);
      } else {
        setError('Failed to load discounts');
        // Set fallback data to prevent blank page
        setDiscounts([
          {
            id: '1',
            name: 'Summer Sale',
            description: '20% off all summer items',
            type: 'percentage',
            value: 20,
            scope: 'all_products',
            scope_ids: null,
            min_amount: 50,
            max_discount: 100,
            start_date: '2025-06-01',
            end_date: '2025-08-31',
            usage_limit: 1000,
            used_count: 150,
            status: 'active',
            created_at: '2025-06-01 00:00:00'
          }
        ]);
      }
    } catch (err) {
      setError('Error loading discounts: ' + err.message);
      console.error('Discounts error:', err);
      // Set fallback data to prevent blank page
      setDiscounts([
        {
          id: '1',
          name: 'Summer Sale',
          description: '20% off all summer items',
          type: 'percentage',
          value: 20,
          scope: 'all_products',
          scope_ids: null,
          min_amount: 50,
          max_discount: 100,
          start_date: '2025-06-01',
          end_date: '2025-08-31',
          usage_limit: 1000,
          used_count: 150,
          status: 'active',
          created_at: '2025-06-01 00:00:00'
        }
      ]);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await categoriesAPI.getAll();
      if (response.data.success) {
        setCategories(response.data.data);
      } else {
        console.error('Categories API returned error');
        setCategories([]);
      }
    } catch (err) {
      console.error('Categories error:', err);
      setCategories([]);
    }
  };

  const fetchProducts = async () => {
    try {
      const response = await productsAPI.getAll();
      if (response.data.success) {
        setProducts(response.data.data);
      } else {
        console.error('Products API returned error');
        setProducts([]);
      }
    } catch (err) {
      console.error('Products error:', err);
      setProducts([]);
    }
  };

  const fetchLocations = async () => {
    try {
      const response = await locationsAPI.getAll();
      if (response.data.success) {
        setLocations(response.data.data);
      } else {
        console.error('Locations API returned error');
        setLocations([]);
      }
    } catch (err) {
      console.error('Locations error:', err);
      setLocations([]);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const discountData = {
        ...formData,
        value: parseFloat(formData.value),
        min_amount: formData.min_amount ? parseFloat(formData.min_amount) : null,
        max_discount: formData.max_discount ? parseFloat(formData.max_discount) : null,
        usage_limit: formData.usage_limit ? parseInt(formData.usage_limit) : null,
        scope_ids: formData.scope_ids.length > 0 ? formData.scope_ids : null
      };

      if (editingDiscount) {
        const response = await discountsAPI.update({ id: editingDiscount.id, ...discountData });
        if (response.data.success) {
          setShowAddModal(false);
          setEditingDiscount(null);
          resetForm();
          fetchDiscounts();
        }
      } else {
        const response = await discountsAPI.create(discountData);
        if (response.data.success) {
          setShowAddModal(false);
          resetForm();
          fetchDiscounts();
        }
      }
    } catch (err) {
      setError('Failed to save discount: ' + err.message);
      console.error('Save discount error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this discount?')) return;

    try {
      setLoading(true);
      const response = await discountsAPI.delete(id);
      if (response.data.success) {
        fetchDiscounts();
      }
    } catch (err) {
      setError('Failed to delete discount: ' + err.message);
      console.error('Delete discount error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (discount) => {
    setEditingDiscount(discount);
    setFormData({
      name: discount.name,
      description: discount.description || '',
      type: discount.type,
      value: discount.value.toString(),
      scope: discount.scope,
      scope_ids: discount.scope_ids ? JSON.parse(discount.scope_ids) : [],
      min_amount: discount.min_amount ? discount.min_amount.toString() : '',
      max_discount: discount.max_discount ? discount.max_discount.toString() : '',
      start_date: discount.start_date ? discount.start_date.split('T')[0] : '',
      end_date: discount.end_date ? discount.end_date.split('T')[0] : '',
      usage_limit: discount.usage_limit ? discount.usage_limit.toString() : '',
      status: discount.status
    });
    setShowAddModal(true);
  };

  const resetForm = () => {
    setFormData({
      name: '',
      description: '',
      type: 'percentage',
      value: '',
      scope: 'all_products',
      scope_ids: [],
      min_amount: '',
      max_discount: '',
      start_date: '',
      end_date: '',
      usage_limit: '',
      status: 'active'
    });
    setEditingDiscount(null);
  };

  const handleNewDiscount = () => {
    resetForm();
    setShowAddModal(true);
  };

  const getScopeOptions = () => {
    switch (formData.scope) {
      case 'category':
        return categories.map(cat => ({ id: cat.id, name: cat.name }));
      case 'product':
        return products.map(prod => ({ id: prod.id, name: prod.name }));
      case 'location':
        return locations.map(loc => ({ id: loc.id, name: loc.name }));
      default:
        return [];
    }
  };

  const filteredDiscounts = discounts.filter(discount => {
    const matchesSearch = discount.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         discount.description?.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || discount.status === filterStatus;
    const matchesType = filterType === 'all' || discount.type === filterType;
    return matchesSearch && matchesStatus && matchesType;
  });

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'inactive': return 'bg-gray-100 text-gray-800';
      case 'expired': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getTypeIcon = (type) => {
    return type === 'percentage' ? <FiPercent className="h-4 w-4" /> : <FiDollarSign className="h-4 w-4" />;
  };

  const getScopeIcon = (scope) => {
    switch (scope) {
      case 'all_products': return <FiGlobe className="h-4 w-4" />;
      case 'category': return <FiTag className="h-4 w-4" />;
      case 'product': return <FiPackage className="h-4 w-4" />;
      case 'location': return <FiMapPin className="h-4 w-4" />;
      default: return <FiGlobe className="h-4 w-4" />;
    }
  };

  // Show loading state while checking permissions
  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#e41e5b] mx-auto mb-4"></div>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  if (!canManageDiscounts) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <FiAlertCircle className="h-16 w-16 text-red-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Access Denied</h2>
          <p className="text-gray-600">
            You don't have permission to manage discounts. 
            {user.role === 'super_admin' && ' Super admins should use the super admin dashboard.'}
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                <FiPercent className="h-8 w-8 mr-3 text-[#e41e5b]" />
                Discount Management
              </h1>
              <p className="mt-2 text-gray-600">Manage discounts and promotional offers for your business</p>
            </div>
            <button
              onClick={handleNewDiscount}
              className="bg-[#e41e5b] text-white px-6 py-3 rounded-lg hover:bg-[#9a0864] transition-colors flex items-center"
            >
              <FiPlus className="h-5 w-5 mr-2" />
              New Discount
            </button>
          </div>
        </div>

        {/* Error Display */}
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div className="flex items-center">
              <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
              <span className="text-red-800">{error}</span>
            </div>
          </div>
        )}

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <div className="relative">
                <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search discounts..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="expired">Expired</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <select
                value={filterType}
                onChange={(e) => setFilterType(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
              >
                <option value="all">All Types</option>
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={fetchDiscounts}
                className="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center"
              >
                <FiRotateCw className="h-4 w-4 mr-2" />
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Discounts Grid */}
        {loading ? (
          <div className="text-center py-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#e41e5b] mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading discounts...</p>
          </div>
        ) : error ? (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <div className="flex items-center">
              <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
              <span className="text-red-800">{error}</span>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredDiscounts.map(discount => (
              <div key={discount.id} className="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div className="p-6">
                  {/* Header */}
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-2">
                      {getTypeIcon(discount.type)}
                      <div>
                        <h3 className="font-semibold text-gray-900">{discount.name}</h3>
                        <p className="text-sm text-gray-500">{discount.description}</p>
                      </div>
                    </div>
                    <div className="flex items-center space-x-2">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(discount.status)}`}>
                        {discount.status}
                      </span>
                      <div className="relative">
                        <button className="text-gray-400 hover:text-gray-600">
                          <FiEye className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Discount Details */}
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Discount Value:</span>
                      <span className="font-semibold text-[#e41e5b]">
                        {discount.type === 'percentage' ? `${discount.value}%` : formatCurrency(discount.value)}
                      </span>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Scope:</span>
                      <div className="flex items-center space-x-1">
                        {getScopeIcon(discount.scope)}
                        <span className="text-sm font-medium capitalize">{discount.scope.replace('_', ' ')}</span>
                      </div>
                    </div>

                    {discount.min_amount && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Min Amount:</span>
                        <span className="text-sm font-medium">{formatCurrency(discount.min_amount)}</span>
                      </div>
                    )}

                    {discount.max_discount && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Max Discount:</span>
                        <span className="text-sm font-medium">{formatCurrency(discount.max_discount)}</span>
                      </div>
                    )}

                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Usage:</span>
                      <span className="text-sm font-medium">
                        {discount.used_count || 0}
                        {discount.usage_limit && ` / ${discount.usage_limit}`}
                      </span>
                    </div>

                    {(discount.start_date || discount.end_date) && (
                      <div className="flex items-center space-x-2 text-sm text-gray-600">
                        <FiCalendar className="h-4 w-4" />
                        <span>
                          {discount.start_date && new Date(discount.start_date).toLocaleDateString()}
                          {discount.start_date && discount.end_date && ' - '}
                          {discount.end_date && new Date(discount.end_date).toLocaleDateString()}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="mt-6 flex items-center justify-between pt-4 border-t border-gray-200">
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => handleEdit(discount)}
                        className="text-[#e41e5b] hover:text-[#9a0864] transition-colors"
                      >
                        <FiEdit className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => handleDelete(discount.id)}
                        className="text-red-600 hover:text-red-800 transition-colors"
                      >
                        <FiTrash className="h-4 w-4" />
                      </button>
                    </div>
                    <div className="text-xs text-gray-500">
                      Created {new Date(discount.created_at).toLocaleDateString()}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {filteredDiscounts.length === 0 && !loading && (
          <div className="text-center py-12">
            <FiPercent className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600">No discounts found</p>
          </div>
        )}
      </div>

      {/* Add/Edit Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-semibold text-gray-900">
                  {editingDiscount ? 'Edit Discount' : 'New Discount'}
                </h2>
                <button
                  onClick={() => setShowAddModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <FiX className="h-6 w-6" />
                </button>
              </div>

              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input
                      type="text"
                      required
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                    <select
                      required
                      value={formData.type}
                      onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    >
                      <option value="percentage">Percentage</option>
                      <option value="fixed">Fixed Amount</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Value * ({formData.type === 'percentage' ? '%' : 'GHS'})
                    </label>
                    <input
                      type="number"
                      required
                      min="0"
                      max={formData.type === 'percentage' ? "100" : undefined}
                      step={formData.type === 'percentage' ? "0.01" : "0.01"}
                      value={formData.value}
                      onChange={(e) => setFormData({ ...formData, value: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Scope *</label>
                    <select
                      required
                      value={formData.scope}
                      onChange={(e) => setFormData({ ...formData, scope: e.target.value, scope_ids: [] })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    >
                      <option value="all_products">All Products</option>
                      <option value="category">Specific Categories</option>
                      <option value="product">Specific Products</option>
                      <option value="location">Specific Locations</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Minimum Amount</label>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={formData.min_amount}
                      onChange={(e) => setFormData({ ...formData, min_amount: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Maximum Discount</label>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={formData.max_discount}
                      onChange={(e) => setFormData({ ...formData, max_discount: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input
                      type="date"
                      value={formData.start_date}
                      onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input
                      type="date"
                      value={formData.end_date}
                      onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                    <input
                      type="number"
                      min="1"
                      value={formData.usage_limit}
                      onChange={(e) => setFormData({ ...formData, usage_limit: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select
                      value={formData.status}
                      onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>

                {formData.scope !== 'all_products' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Select {formData.scope.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </label>
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-32 overflow-y-auto border border-gray-300 rounded-lg p-3">
                      {getScopeOptions().map(option => (
                        <label key={option.id} className="flex items-center space-x-2">
                          <input
                            type="checkbox"
                            checked={formData.scope_ids.includes(option.id)}
                            onChange={(e) => {
                              if (e.target.checked) {
                                setFormData({
                                  ...formData,
                                  scope_ids: [...formData.scope_ids, option.id]
                                });
                              } else {
                                setFormData({
                                  ...formData,
                                  scope_ids: formData.scope_ids.filter(id => id !== option.id)
                                });
                              }
                            }}
                            className="rounded border-gray-300 text-[#e41e5b] focus:ring-[#e41e5b]"
                          />
                          <span className="text-sm">{option.name}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
                  <textarea
                    rows="3"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                  <button
                    type="button"
                    onClick={() => setShowAddModal(false)}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-6 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    {loading ? 'Saving...' : (editingDiscount ? 'Update Discount' : 'Create Discount')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DiscountsPage;
