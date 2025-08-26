import React, { useState, useEffect } from 'react';
import { 
  FiPlus, FiEdit, FiTrash, FiSearch, FiMapPin, FiAlertCircle, 
  FiCheck, FiX, FiEye, FiUsers, FiShoppingCart, FiSettings,
  FiPhone, FiMail, FiGlobe, FiClock, FiDollarSign, FiTrendingUp
} from 'react-icons/fi';

const LocationsPage = () => {
  const [locations, setLocations] = useState([]);
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingLocation, setEditingLocation] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    type: 'store',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'Ghana',
    phone: '',
    email: '',
    manager_id: '',
    timezone: 'Africa/Accra',
    currency: 'GHS',
    tax_rate: 15.00,
    status: 'active',
    users: []
  });

  const locationTypes = [
    { value: 'store', label: 'Store', icon: FiShoppingCart },
    { value: 'restaurant', label: 'Restaurant', icon: FiShoppingCart },
    { value: 'warehouse', label: 'Warehouse', icon: FiShoppingCart },
    { value: 'office', label: 'Office', icon: FiShoppingCart }
  ];

  const fetchLocations = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch('/api/locations', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setLocations(data.data);
      } else {
        setError('Failed to load locations');
      }
    } catch (err) {
      setError('Error loading locations');
      console.error('Locations error:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchUsers = async () => {
    try {
      const response = await fetch('/api/users', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setUsers(data.data);
      }
    } catch (err) {
      console.error('Users error:', err);
    }
  };

  useEffect(() => {
    fetchLocations();
    fetchUsers();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.name.trim()) {
      setError('Location name is required');
      return;
    }

    try {
      setError(null);
      const url = editingLocation 
        ? `/api/locations/${editingLocation.id}`
        : '/api/locations';
      
      const method = editingLocation ? 'PUT' : 'POST';
      
      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });
      
      const data = await response.json();
      
      if (data.success) {
        setShowModal(false);
        setEditingLocation(null);
        setFormData({
          name: '',
          type: 'store',
          address: '',
          city: '',
          state: '',
          postal_code: '',
          country: 'Ghana',
          phone: '',
          email: '',
          manager_id: '',
          timezone: 'Africa/Accra',
          currency: 'GHS',
          tax_rate: 15.00,
          status: 'active',
          users: []
        });
        fetchLocations();
      } else {
        setError(data.error || 'Failed to save location');
      }
    } catch (err) {
      setError('Error saving location');
      console.error('Save error:', err);
    }
  };

  const handleEdit = (location) => {
    setEditingLocation(location);
    setFormData({
      name: location.name,
      type: location.type || 'store',
      address: location.address || '',
      city: location.city || '',
      state: location.state || '',
      postal_code: location.postal_code || '',
      country: location.country || 'Ghana',
      phone: location.phone || '',
      email: location.email || '',
      manager_id: location.manager_id || '',
      timezone: location.timezone || 'Africa/Accra',
      currency: location.currency || 'GHS',
      tax_rate: location.tax_rate || 15.00,
      status: location.status || 'active',
      users: location.users || []
    });
    setShowModal(true);
  };

  const handleDelete = async (locationId) => {
    if (!window.confirm('Are you sure you want to delete this location? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(`/api/locations/${locationId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (data.success) {
        fetchLocations();
      } else {
        setError(data.error || 'Failed to delete location');
      }
    } catch (err) {
      setError('Error deleting location');
      console.error('Delete error:', err);
    }
  };

  const handleNewLocation = () => {
    setEditingLocation(null);
    setFormData({
      name: '',
      type: 'store',
      address: '',
      city: '',
      state: '',
      postal_code: '',
      country: 'Ghana',
      phone: '',
      email: '',
      manager_id: '',
      timezone: 'Africa/Accra',
      currency: 'GHS',
      tax_rate: 15.00,
      status: 'active',
      users: []
    });
    setShowModal(true);
  };

  const addUserToLocation = () => {
    setFormData({
      ...formData,
      users: [...formData.users, { user_id: '', role: 'staff', permissions: {} }]
    });
  };

  const removeUserFromLocation = (index) => {
    setFormData({
      ...formData,
      users: formData.users.filter((_, i) => i !== index)
    });
  };

  const updateUserInLocation = (index, field, value) => {
    const newUsers = [...formData.users];
    newUsers[index][field] = value;
    setFormData({
      ...formData,
      users: newUsers
    });
  };

  const filteredLocations = locations.filter(location =>
    location.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (location.address && location.address.toLowerCase().includes(searchTerm.toLowerCase())) ||
    (location.city && location.city.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'inactive': return 'bg-gray-100 text-gray-800';
      case 'maintenance': return 'bg-yellow-100 text-yellow-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getTypeIcon = (type) => {
    const typeConfig = locationTypes.find(t => t.value === type);
    return typeConfig ? typeConfig.icon : FiMapPin;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading Locations...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Locations</h1>
            <p className="text-[#746354] mt-1">
              Manage your stores, restaurants, and locations
            </p>
          </div>
          <button
            onClick={handleNewLocation}
            className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
          >
            <FiPlus className="h-4 w-4 mr-2" />
            Add Location
          </button>
        </div>
      </div>

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

      {/* Search */}
      <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
        <div className="relative">
          <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#746354]" />
          <input
            type="text"
            placeholder="Search locations..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
          />
        </div>
      </div>

      {/* Locations Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredLocations.map((location) => {
          const TypeIcon = getTypeIcon(location.type);
          return (
            <div
              key={location.id}
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex items-start justify-between mb-4">
                <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-lg flex items-center justify-center">
                  <TypeIcon className="h-6 w-6 text-[#e41e5b]" />
                </div>
                <div className="flex items-center space-x-2">
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(location.status)}`}>
                    {location.status}
                  </span>
                  <button
                    onClick={() => handleEdit(location)}
                    className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-gray-100 rounded-lg transition-colors"
                    title="Edit Location"
                  >
                    <FiEdit className="h-4 w-4" />
                  </button>
                  <button
                    onClick={() => handleDelete(location.id)}
                    className="p-2 text-[#746354] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Delete Location"
                  >
                    <FiTrash className="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div>
                <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">
                  {location.name}
                </h3>
                <p className="text-sm text-[#746354] mb-4 capitalize">
                  {location.type}
                </p>
                
                <div className="space-y-2 mb-4">
                  {location.address && (
                    <div className="flex items-center text-sm text-[#746354]">
                      <FiMapPin className="h-4 w-4 mr-2" />
                      <span>{location.address}</span>
                    </div>
                  )}
                  {location.phone && (
                    <div className="flex items-center text-sm text-[#746354]">
                      <FiPhone className="h-4 w-4 mr-2" />
                      <span>{location.phone}</span>
                    </div>
                  )}
                  {location.email && (
                    <div className="flex items-center text-sm text-[#746354]">
                      <FiMail className="h-4 w-4 mr-2" />
                      <span>{location.email}</span>
                    </div>
                  )}
                </div>

                <div className="flex items-center justify-between text-sm">
                  <div className="flex items-center space-x-4">
                    <div className="flex items-center text-[#746354]">
                      <FiUsers className="h-4 w-4 mr-1" />
                      <span>{location.user_count || 0}</span>
                    </div>
                    <div className="flex items-center text-[#746354]">
                      <FiShoppingCart className="h-4 w-4 mr-1" />
                      <span>{location.sales_count || 0}</span>
                    </div>
                  </div>
                  <div className="text-[#746354]">
                    {location.currency} {location.tax_rate}%
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Empty State */}
      {filteredLocations.length === 0 && (
        <div className="text-center py-12">
          <FiMapPin className="h-12 w-12 text-[#746354] mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">
            {searchTerm ? 'No locations found' : 'No locations yet'}
          </h3>
          <p className="text-[#746354] mb-4">
            {searchTerm 
              ? 'Try adjusting your search terms'
              : 'Create your first location to manage multiple stores'
            }
          </p>
          {!searchTerm && (
            <button
              onClick={handleNewLocation}
              className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors mx-auto"
            >
              <FiPlus className="h-4 w-4 mr-2" />
              Create First Location
            </button>
          )}
        </div>
      )}

      {/* Location Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-[#2c2c2c]">
                {editingLocation ? 'Edit Location' : 'New Location'}
              </h2>
              <button
                onClick={() => setShowModal(false)}
                className="text-[#746354] hover:text-[#2c2c2c]"
              >
                <FiX className="h-6 w-6" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Basic Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Location Name *
                  </label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="e.g., Main Store, Downtown Branch"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Location Type
                  </label>
                  <select
                    value={formData.type}
                    onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  >
                    {locationTypes.map(type => (
                      <option key={type.value} value={type.value}>
                        {type.label}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Address Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Address
                  </label>
                  <input
                    type="text"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="Street address"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    City
                  </label>
                  <input
                    type="text"
                    value={formData.city}
                    onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="City"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    State/Region
                  </label>
                  <input
                    type="text"
                    value={formData.state}
                    onChange={(e) => setFormData({ ...formData, state: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="State or region"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Postal Code
                  </label>
                  <input
                    type="text"
                    value={formData.postal_code}
                    onChange={(e) => setFormData({ ...formData, postal_code: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="Postal code"
                  />
                </div>
              </div>

              {/* Contact Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Phone
                  </label>
                  <input
                    type="tel"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="Phone number"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="Email address"
                  />
                </div>
              </div>

              {/* Settings */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Manager
                  </label>
                  <select
                    value={formData.manager_id}
                    onChange={(e) => setFormData({ ...formData, manager_id: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  >
                    <option value="">Select Manager</option>
                    {users.map(user => (
                      <option key={user.id} value={user.id}>
                        {user.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Currency
                  </label>
                  <select
                    value={formData.currency}
                    onChange={(e) => setFormData({ ...formData, currency: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  >
                    <option value="GHS">GHS (Ghanaian Cedi)</option>
                    <option value="USD">USD (US Dollar)</option>
                    <option value="EUR">EUR (Euro)</option>
                    <option value="GBP">GBP (British Pound)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                    Tax Rate (%)
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={formData.tax_rate}
                    onChange={(e) => setFormData({ ...formData, tax_rate: parseFloat(e.target.value) })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="15.00"
                  />
                </div>
              </div>

              {/* Status */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Status
                </label>
                <select
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="maintenance">Maintenance</option>
                </select>
              </div>

              {/* Action Buttons */}
              <div className="flex space-x-3 pt-4">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  {editingLocation ? 'Update Location' : 'Create Location'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default LocationsPage;
