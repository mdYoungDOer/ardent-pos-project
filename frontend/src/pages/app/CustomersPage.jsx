import React, { useState, useEffect } from 'react';
import { FiPlus, FiEdit, FiTrash, FiSearch, FiUser, FiAlertCircle, FiPhone, FiMail } from 'react-icons/fi';
import { customersAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

const CustomersPage = () => {
  const { user } = useAuth();
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(null);
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: ''
  });

  const fetchCustomers = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await customersAPI.getAll();
      if (response.data.success) {
        setCustomers(response.data.data);
      } else {
        setError('Failed to load customers');
      }
    } catch (err) {
      setError('Error loading customers');
      console.error('Customers error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCustomers();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingCustomer) {
        await customersAPI.update({ ...formData, id: editingCustomer.id });
      } else {
        await customersAPI.create(formData);
      }
      setShowAddModal(false);
      setEditingCustomer(null);
      setFormData({ first_name: '', last_name: '', email: '', phone: '', address: '' });
      fetchCustomers();
    } catch (err) {
      console.error('Customer save error:', err);
    }
  };

  const handleDelete = async (customerId) => {
    if (window.confirm('Are you sure you want to delete this customer?')) {
      try {
        await customersAPI.delete(customerId);
        fetchCustomers();
      } catch (err) {
        console.error('Delete error:', err);
      }
    }
  };

  const handleEdit = (customer) => {
    setEditingCustomer(customer);
    setFormData({
      first_name: customer.first_name,
      last_name: customer.last_name,
      email: customer.email || '',
      phone: customer.phone || '',
      address: customer.address || ''
    });
    setShowAddModal(true);
  };

  const filteredCustomers = customers.filter(customer =>
    customer.first_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    customer.last_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (customer.email && customer.email.toLowerCase().includes(searchTerm.toLowerCase())) ||
    (customer.phone && customer.phone.includes(searchTerm))
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading customers...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Customers</h1>
            <p className="text-[#746354] mt-1">
              Manage your customer database and relationships
            </p>
          </div>
          <button
            onClick={() => setShowAddModal(true)}
            className="flex items-center px-6 py-3 bg-[#e41e5b] text-white rounded-xl hover:bg-[#9a0864] transition-colors shadow-sm"
          >
            <FiPlus className="h-5 w-5 mr-2" />
            Add Customer
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6 mb-6">
        <div className="relative">
          <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-[#746354]" />
          <input
            type="text"
            placeholder="Search customers by name, email, or phone..."
            className="w-full pl-10 pr-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Customers Grid */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 overflow-hidden">
        {error ? (
          <div className="p-8 text-center">
            <FiAlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Customers</h3>
            <p className="text-red-600 mb-4">{error}</p>
            <button
              onClick={fetchCustomers}
              className="bg-[#e41e5b] text-white px-6 py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              Try Again
            </button>
          </div>
        ) : filteredCustomers.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-[#746354]/10">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Customer</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Contact</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Address</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#746354]/10">
                {filteredCustomers.map((customer) => (
                  <tr key={customer.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <div className="flex items-center">
                        <div className="w-12 h-12 bg-[#a67c00]/10 rounded-xl flex items-center justify-center mr-4">
                          <FiUser className="h-6 w-6 text-[#a67c00]" />
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[#2c2c2c]">
                            {customer.first_name} {customer.last_name}
                          </div>
                          <div className="text-sm text-[#746354]">Customer ID: {customer.id.slice(-8)}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="space-y-1">
                        {customer.email && (
                          <div className="flex items-center text-sm text-[#746354]">
                            <FiMail className="h-4 w-4 mr-2" />
                            {customer.email}
                          </div>
                        )}
                        {customer.phone && (
                          <div className="flex items-center text-sm text-[#746354]">
                            <FiPhone className="h-4 w-4 mr-2" />
                            {customer.phone}
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm text-[#746354] max-w-xs truncate">
                        {customer.address || 'No address provided'}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => handleEdit(customer)}
                          className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors"
                          title="Edit customer"
                        >
                          <FiEdit className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(customer.id)}
                          className="p-2 text-[#746354] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Delete customer"
                        >
                          <FiTrash className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <FiUser className="h-16 w-16 text-[#746354]/40 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">No customers found</h3>
            <p className="text-[#746354] mb-6">
              {searchTerm 
                ? 'Try adjusting your search criteria'
                : 'Get started by adding your first customer'
              }
            </p>
            <button
              onClick={() => setShowAddModal(true)}
              className="bg-[#e41e5b] text-white px-6 py-3 rounded-xl hover:bg-[#9a0864] transition-colors"
            >
              Add Customer
            </button>
          </div>
        )}
      </div>

      {/* Add/Edit Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full p-6">
            <h2 className="text-xl font-semibold text-[#2c2c2c] mb-4">
              {editingCustomer ? 'Edit Customer' : 'Add Customer'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    First Name
                  </label>
                  <input
                    type="text"
                    required
                    className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                    value={formData.first_name}
                    onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Last Name
                  </label>
                  <input
                    type="text"
                    required
                    className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                    value={formData.last_name}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Email
                </label>
                <input
                  type="email"
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Phone
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
              <div className="flex space-x-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-[#e41e5b] text-white py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  {editingCustomer ? 'Update' : 'Add'} Customer
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowAddModal(false);
                    setEditingCustomer(null);
                    setFormData({ first_name: '', last_name: '', email: '', phone: '', address: '' });
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

export default CustomersPage;
