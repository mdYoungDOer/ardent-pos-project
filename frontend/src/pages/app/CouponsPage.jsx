import React, { useState, useEffect } from 'react';
import {
  FiPlus, FiEdit, FiTrash, FiSearch, FiPercent, FiAlertCircle,
  FiCalendar, FiDollarSign, FiTag, FiMapPin, FiPackage, FiFilter,
  FiEye, FiEyeOff, FiClock, FiTrendingUp, FiUsers, FiGlobe, FiCopy,
  FiRefreshCw, FiX, FiGift
} from 'react-icons/fi';
import { couponsAPI, categoriesAPI, productsAPI, locationsAPI } from '../../services/api';
import useAuthStore from '../../stores/authStore';

const CouponsPage = () => {
  const { user } = useAuthStore();
  const [coupons, setCoupons] = useState([]);
  const [categories, setCategories] = useState([]);
  const [products, setProducts] = useState([]);
  const [locations, setLocations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [filterType, setFilterType] = useState('all');
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingCoupon, setEditingCoupon] = useState(null);
  const [formData, setFormData] = useState({
    code: '',
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
    per_customer_limit: '',
    status: 'active'
  });

  // Check if user has permission to manage coupons
  const canManageCoupons = ['admin', 'manager'].includes(user?.role);

  useEffect(() => {
    if (canManageCoupons) {
      fetchCoupons();
      fetchCategories();
      fetchProducts();
      fetchLocations();
    }
  }, [canManageCoupons]);

  const fetchCoupons = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await couponsAPI.getAll();
      if (response.data.success) {
        setCoupons(response.data.data);
      } else {
        setError('Failed to load coupons');
      }
    } catch (err) {
      setError('Error loading coupons: ' + err.message);
      console.error('Coupons error:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await categoriesAPI.getAll();
      if (response.data.success) {
        setCategories(response.data.data);
      }
    } catch (err) {
      console.error('Categories error:', err);
    }
  };

  const fetchProducts = async () => {
    try {
      const response = await productsAPI.getAll();
      if (response.data.success) {
        setProducts(response.data.data);
      }
    } catch (err) {
      console.error('Products error:', err);
    }
  };

  const fetchLocations = async () => {
    try {
      const response = await locationsAPI.getAll();
      if (response.data.success) {
        setLocations(response.data.data);
      }
    } catch (err) {
      console.error('Locations error:', err);
    }
  };

  const generateCouponCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    setFormData({ ...formData, code: result });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const couponData = {
        ...formData,
        value: parseFloat(formData.value),
        min_amount: formData.min_amount ? parseFloat(formData.min_amount) : null,
        max_discount: formData.max_discount ? parseFloat(formData.max_discount) : null,
        usage_limit: formData.usage_limit ? parseInt(formData.usage_limit) : null,
        per_customer_limit: formData.per_customer_limit ? parseInt(formData.per_customer_limit) : null,
        scope_ids: formData.scope_ids.length > 0 ? formData.scope_ids : null
      };

      if (editingCoupon) {
        const response = await couponsAPI.update({ id: editingCoupon.id, ...couponData });
        if (response.data.success) {
          setShowAddModal(false);
          setEditingCoupon(null);
          resetForm();
          fetchCoupons();
        }
      } else {
        const response = await couponsAPI.create(couponData);
        if (response.data.success) {
          setShowAddModal(false);
          resetForm();
          fetchCoupons();
        }
      }
    } catch (err) {
      setError('Failed to save coupon: ' + err.message);
      console.error('Save coupon error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this coupon?')) return;

    try {
      setLoading(true);
      const response = await couponsAPI.delete(id);
      if (response.data.success) {
        fetchCoupons();
      }
    } catch (err) {
      setError('Failed to delete coupon: ' + err.message);
      console.error('Delete coupon error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (coupon) => {
    setEditingCoupon(coupon);
    setFormData({
      code: coupon.code,
      name: coupon.name,
      description: coupon.description || '',
      type: coupon.type,
      value: coupon.value.toString(),
      scope: coupon.scope,
      scope_ids: coupon.scope_ids ? JSON.parse(coupon.scope_ids) : [],
      min_amount: coupon.min_amount ? coupon.min_amount.toString() : '',
      max_discount: coupon.max_discount ? coupon.max_discount.toString() : '',
      start_date: coupon.start_date ? coupon.start_date.split('T')[0] : '',
      end_date: coupon.end_date ? coupon.end_date.split('T')[0] : '',
      usage_limit: coupon.usage_limit ? coupon.usage_limit.toString() : '',
      per_customer_limit: coupon.per_customer_limit ? coupon.per_customer_limit.toString() : '',
      status: coupon.status
    });
    setShowAddModal(true);
  };

  const resetForm = () => {
    setFormData({
      code: '',
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
      per_customer_limit: '',
      status: 'active'
    });
    setEditingCoupon(null);
  };

  const handleNewCoupon = () => {
    resetForm();
    generateCouponCode();
    setShowAddModal(true);
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
      // You could add a toast notification here
      console.log('Copied to clipboard');
    });
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

  const filteredCoupons = coupons.filter(coupon => {
    const matchesSearch = coupon.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         coupon.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         coupon.description?.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || coupon.status === filterStatus;
    const matchesType = filterType === 'all' || coupon.type === filterType;
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

  if (!canManageCoupons) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <FiAlertCircle className="h-16 w-16 text-red-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Access Denied</h2>
          <p className="text-gray-600">You don't have permission to manage coupons.</p>
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
                <FiGift className="h-8 w-8 mr-3 text-[#e41e5b]" />
                Coupon Management
              </h1>
              <p className="mt-2 text-gray-600">Manage promotional coupons and discount codes for your business</p>
            </div>
            <button
              onClick={handleNewCoupon}
              className="bg-[#e41e5b] text-white px-6 py-3 rounded-lg hover:bg-[#9a0864] transition-colors flex items-center"
            >
              <FiPlus className="h-5 w-5 mr-2" />
              New Coupon
            </button>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <div className="relative">
                <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search coupons..."
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
                onClick={fetchCoupons}
                className="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center"
              >
                <FiRefreshCw className="h-4 w-4 mr-2" />
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Coupons Grid */}
        {loading ? (
          <div className="text-center py-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#e41e5b] mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading coupons...</p>
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
            {filteredCoupons.map(coupon => (
              <div key={coupon.id} className="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div className="p-6">
                  {/* Header */}
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-2">
                      {getTypeIcon(coupon.type)}
                      <div>
                        <h3 className="font-semibold text-gray-900">{coupon.name}</h3>
                        <p className="text-sm text-gray-500">{coupon.description}</p>
                      </div>
                    </div>
                    <div className="flex items-center space-x-2">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(coupon.status)}`}>
                        {coupon.status}
                      </span>
                      <div className="relative">
                        <button className="text-gray-400 hover:text-gray-600">
                          <FiEye className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Coupon Code */}
                  <div className="bg-gray-50 rounded-lg p-3 mb-4">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Coupon Code:</span>
                      <div className="flex items-center space-x-2">
                        <span className="font-mono font-bold text-[#e41e5b] text-lg">{coupon.code}</span>
                        <button
                          onClick={() => copyToClipboard(coupon.code)}
                          className="text-gray-400 hover:text-gray-600 transition-colors"
                          title="Copy to clipboard"
                        >
                          <FiCopy className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Coupon Details */}
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Discount Value:</span>
                      <span className="font-semibold text-[#e41e5b]">
                        {coupon.type === 'percentage' ? `${coupon.value}%` : formatCurrency(coupon.value)}
                      </span>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Scope:</span>
                      <div className="flex items-center space-x-1">
                        {getScopeIcon(coupon.scope)}
                        <span className="text-sm font-medium capitalize">{coupon.scope.replace('_', ' ')}</span>
                      </div>
                    </div>

                    {coupon.min_amount && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Min Amount:</span>
                        <span className="text-sm font-medium">{formatCurrency(coupon.min_amount)}</span>
                      </div>
                    )}

                    {coupon.max_discount && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Max Discount:</span>
                        <span className="text-sm font-medium">{formatCurrency(coupon.max_discount)}</span>
                      </div>
                    )}

                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Usage:</span>
                      <span className="text-sm font-medium">
                        {coupon.used_count || 0}
                        {coupon.usage_limit && ` / ${coupon.usage_limit}`}
                      </span>
                    </div>

                    {coupon.per_customer_limit && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Per Customer:</span>
                        <span className="text-sm font-medium">{coupon.per_customer_limit} times</span>
                      </div>
                    )}

                    {(coupon.start_date || coupon.end_date) && (
                      <div className="flex items-center space-x-2 text-sm text-gray-600">
                        <FiCalendar className="h-4 w-4" />
                        <span>
                          {coupon.start_date && new Date(coupon.start_date).toLocaleDateString()}
                          {coupon.start_date && coupon.end_date && ' - '}
                          {coupon.end_date && new Date(coupon.end_date).toLocaleDateString()}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="mt-6 flex items-center justify-between pt-4 border-t border-gray-200">
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => handleEdit(coupon)}
                        className="text-[#e41e5b] hover:text-[#9a0864] transition-colors"
                      >
                        <FiEdit className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => handleDelete(coupon.id)}
                        className="text-red-600 hover:text-red-800 transition-colors"
                      >
                        <FiTrash className="h-4 w-4" />
                      </button>
                    </div>
                    <div className="text-xs text-gray-500">
                      Created {new Date(coupon.created_at).toLocaleDateString()}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {filteredCoupons.length === 0 && !loading && (
          <div className="text-center py-12">
            <FiGift className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600">No coupons found</p>
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
                  {editingCoupon ? 'Edit Coupon' : 'New Coupon'}
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
                    <label className="block text-sm font-medium text-gray-700 mb-2">Coupon Code *</label>
                    <div className="flex space-x-2">
                      <input
                        type="text"
                        required
                        value={formData.code}
                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] font-mono"
                        placeholder="e.g., WELCOME20"
                      />
                      {!editingCoupon && (
                        <button
                          type="button"
                          onClick={generateCouponCode}
                          className="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                          Generate
                        </button>
                      )}
                    </div>
                  </div>

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
                    <label className="block text-sm font-medium text-gray-700 mb-2">Per Customer Limit</label>
                    <input
                      type="number"
                      min="1"
                      value={formData.per_customer_limit}
                      onChange={(e) => setFormData({ ...formData, per_customer_limit: e.target.value })}
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
                    {loading ? 'Saving...' : (editingCoupon ? 'Update Coupon' : 'Create Coupon')}
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

export default CouponsPage;
