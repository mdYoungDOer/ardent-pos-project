import React, { useState, useEffect } from 'react';
import {
  FiUsers, FiPlus, FiEdit, FiTrash, FiSearch, FiFilter, FiEye,
  FiShield, FiShieldOff, FiDollarSign, FiActivity, FiCalendar,
  FiMapPin, FiPhone, FiMail, FiPackage, FiTrendingUp, FiTrendingDown,
  FiMoreVertical, FiDownload, FiRefreshCw, FiAlertCircle
} from 'react-icons/fi';
import useAuthStore from '../../stores/authStore';
import { superAdminAPI } from '../../services/api';

const TenantManagementPage = () => {
  const { user } = useAuthStore();
  const [tenants, setTenants] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingTenant, setEditingTenant] = useState(null);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
    plan: 'basic',
    status: 'active'
  });

  // Fetch real data from API
  useEffect(() => {
    const fetchTenants = async () => {
      setLoading(true);
      try {
        const params = {
          page: 1,
          limit: 50,
          search: searchTerm,
          status: filterStatus !== 'all' ? filterStatus : undefined
        };
        
        const response = await superAdminAPI.getTenants(params);
        if (response.data.success) {
          setTenants(response.data.data.tenants);
        } else {
          setError('Failed to load tenants');
        }
      } catch (error) {
        console.error('Error fetching tenants:', error);
        setError('Failed to load tenants');
      } finally {
        setLoading(false);
      }
    };

    fetchTenants();
  }, [searchTerm, filterStatus]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingTenant) {
        // Update existing tenant
        await superAdminAPI.updateTenant(editingTenant.id, formData);
      } else {
        // Create new tenant
        await superAdminAPI.createTenant(formData);
      }
      
      setShowAddModal(false);
      setEditingTenant(null);
      setFormData({
        name: '',
        email: '',
        phone: '',
        address: '',
        plan: 'basic',
        status: 'active'
      });
      
      // Refresh tenants list
      const params = {
        page: 1,
        limit: 50,
        search: searchTerm,
        status: filterStatus !== 'all' ? filterStatus : undefined
      };
      const response = await superAdminAPI.getTenants(params);
      if (response.data.success) {
        setTenants(response.data.data.tenants);
      }
    } catch (error) {
      console.error('Error saving tenant:', error);
      setError('Failed to save tenant');
    }
  };

  const handleDelete = async (tenantId) => {
    if (window.confirm('Are you sure you want to delete this tenant? This action cannot be undone.')) {
      try {
        await superAdminAPI.deleteTenant(tenantId);
        // Refresh tenants list
        const params = {
          page: 1,
          limit: 50,
          search: searchTerm,
          status: filterStatus !== 'all' ? filterStatus : undefined
        };
        const response = await superAdminAPI.getTenants(params);
        if (response.data.success) {
          setTenants(response.data.data.tenants);
        }
      } catch (error) {
        console.error('Error deleting tenant:', error);
        setError('Failed to delete tenant');
      }
    }
  };

  const handleStatusChange = async (tenantId, newStatus) => {
    try {
      await superAdminAPI.updateTenant(tenantId, { status: newStatus });
      // Refresh tenants list
      const params = {
        page: 1,
        limit: 50,
        search: searchTerm,
        status: filterStatus !== 'all' ? filterStatus : undefined
      };
      const response = await superAdminAPI.getTenants(params);
      if (response.data.success) {
        setTenants(response.data.data.tenants);
      }
    } catch (error) {
      console.error('Error updating tenant status:', error);
      setError('Failed to update tenant status');
    }
  };

  const filteredTenants = tenants.filter(tenant => {
    const matchesSearch = tenant.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         tenant.email.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || tenant.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GH');
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'text-green-600 bg-green-100';
      case 'suspended': return 'text-red-600 bg-red-100';
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getPlanColor = (plan) => {
    switch (plan) {
      case 'enterprise': return 'text-purple-600 bg-purple-100';
      case 'premium': return 'text-blue-600 bg-blue-100';
      case 'basic': return 'text-gray-600 bg-gray-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading tenants...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Error Display */}
      {error && (
        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center">
            <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
            <span className="text-red-800">{error}</span>
            <button
              onClick={() => setError(null)}
              className="ml-auto text-red-500 hover:text-red-700"
            >
              ×
            </button>
          </div>
        </div>
      )}
      
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Tenant Management</h1>
            <p className="text-[#746354] mt-1">
              Manage all tenants and their subscriptions
            </p>
          </div>
          <div className="flex items-center space-x-4">
            <button className="flex items-center px-4 py-2 bg-gray-100 text-[#2c2c2c] rounded-lg hover:bg-gray-200 transition-colors">
              <FiDownload className="h-4 w-4 mr-2" />
              Export
            </button>
            <button className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
              <FiPlus className="h-4 w-4 mr-2" />
              Add Tenant
            </button>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Tenants</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{tenants.length}</p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiUsers className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Active Tenants</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{tenants.filter(t => t.status === 'active').length}</p>
            </div>
            <div className="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <FiShield className="h-6 w-6 text-green-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Revenue</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">
                {formatCurrency(tenants.reduce((sum, t) => sum + t.revenue, 0))}
              </p>
            </div>
            <div className="w-12 h-12 bg-[#9a0864]/10 rounded-xl flex items-center justify-center">
              <FiDollarSign className="h-6 w-6 text-[#9a0864]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Pending Approvals</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{tenants.filter(t => t.status === 'pending').length}</p>
            </div>
            <div className="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
              <FiAlertCircle className="h-6 w-6 text-yellow-600" />
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-[#746354]" />
            <input
              type="text"
              placeholder="Search tenants by name or email..."
              className="w-full pl-10 pr-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          
          <div>
            <select
              className="w-full px-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
            >
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
              <option value="pending">Pending</option>
            </select>
          </div>

          <div className="flex items-center space-x-2">
            <button className="flex items-center px-4 py-3 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
              <FiRefreshCw className="h-4 w-4 mr-2" />
              Refresh
            </button>
            <button className="flex items-center px-4 py-3 bg-gray-100 text-[#2c2c2c] rounded-lg hover:bg-gray-200 transition-colors">
              <FiFilter className="h-4 w-4 mr-2" />
              More Filters
            </button>
          </div>
        </div>
      </div>

      {/* Tenants Table */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-[#746354]/10">
              <tr>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Tenant</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Plan</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Status</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Users</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Revenue</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Last Login</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#746354]/10">
              {filteredTenants.map((tenant) => (
                <tr key={tenant.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4">
                    <div className="flex items-center">
                      <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center mr-4">
                        <FiUsers className="h-6 w-6 text-[#e41e5b]" />
                      </div>
                      <div>
                        <div className="text-sm font-semibold text-[#2c2c2c]">{tenant.name}</div>
                        <div className="text-sm text-[#746354]">{tenant.email}</div>
                        <div className="text-xs text-[#746354] flex items-center">
                          <FiMapPin className="h-3 w-3 mr-1" />
                          {tenant.address}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${getPlanColor(tenant.plan)}`}>
                      {tenant.plan}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(tenant.status)}`}>
                      {tenant.status}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm font-semibold text-[#2c2c2c]">{tenant.users}</div>
                    <div className="text-xs text-[#746354]">active users</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm font-semibold text-[#e41e5b]">{formatCurrency(tenant.revenue)}</div>
                    <div className="text-xs text-[#746354]">lifetime</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm text-[#746354]">
                      {tenant.last_login ? formatDate(tenant.last_login) : 'Never'}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center space-x-2">
                      <button
                        className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors"
                        title="View tenant"
                      >
                        <FiEye className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => {
                          setEditingTenant(tenant);
                          setFormData({
                            name: tenant.name,
                            email: tenant.email,
                            phone: tenant.phone,
                            address: tenant.address,
                            plan: tenant.plan,
                            status: tenant.status
                          });
                          setShowAddModal(true);
                        }}
                        className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors"
                        title="Edit tenant"
                      >
                        <FiEdit className="h-4 w-4" />
                      </button>
                      <div className="relative">
                        <button className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors">
                          <FiMoreVertical className="h-4 w-4" />
                        </button>
                        {/* Dropdown menu would go here */}
                      </div>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Add/Edit Tenant Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full p-6">
            <h2 className="text-xl font-semibold text-[#2c2c2c] mb-4">
              {editingTenant ? 'Edit Tenant' : 'Add New Tenant'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Tenant Name
                </label>
                <input
                  type="text"
                  required
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Email Address
                </label>
                <input
                  type="email"
                  required
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Phone Number
                </label>
                <input
                  type="tel"
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Address
                </label>
                <textarea
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  rows="3"
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Plan
                  </label>
                  <select
                    className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                    value={formData.plan}
                    onChange={(e) => setFormData({ ...formData, plan: e.target.value })}
                  >
                    <option value="basic">Basic</option>
                    <option value="premium">Premium</option>
                    <option value="enterprise">Enterprise</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Status
                  </label>
                  <select
                    className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                    value={formData.status}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                  >
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="pending">Pending</option>
                  </select>
                </div>
              </div>
              <div className="flex space-x-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-[#e41e5b] text-white py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  {editingTenant ? 'Update' : 'Add'} Tenant
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowAddModal(false);
                    setEditingTenant(null);
                    setFormData({ name: '', email: '', phone: '', address: '', plan: 'basic', status: 'active' });
                  }}
                  className="flex-1 bg-gray-200 text-[#2c2c2c] py-2 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default TenantManagementPage;
