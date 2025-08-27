import React, { useState, useEffect } from 'react';
import { 
  FaKey, 
  FaCopy, 
  FaEye, 
  FaEyeSlash, 
  FaPlus, 
  FaTrash, 
  FaEdit,
  FaCheck,
  FaTimes,
  FaRefresh,
  FaDownload,
  FaUpload,
  FaShieldAlt,
  FaClock,
  FaUser,
  FaCalendarAlt,
  FaSearch
} from 'react-icons/fa';
import { toast } from 'react-hot-toast';

const SuperAdminAPIKeysPage = () => {
  const [apiKeys, setApiKeys] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedKey, setSelectedKey] = useState(null);
  const [newKeyData, setNewKeyData] = useState({
    name: '',
    description: '',
    permissions: [],
    expiresAt: ''
  });
  const [showSecret, setShowSecret] = useState({});
  const [filter, setFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');

  // Demo API keys data
  const demoApiKeys = [
    {
      id: 1,
      name: 'Production API Key',
      key: 'pk_live_demo_production_key_123456789',
      secret: 'sk_live_demo_secret_key_abcdefghijklmnop',
      description: 'Primary API key for production environment',
      status: 'active',
      permissions: ['read', 'write', 'delete'],
      createdBy: 'Super Admin',
      createdAt: '2024-01-15T10:30:00Z',
      lastUsed: '2024-01-20T14:22:00Z',
      expiresAt: null,
      usageCount: 15420
    },
    {
      id: 2,
      name: 'Development API Key',
      key: 'pk_test_demo_development_key_987654321',
      secret: 'sk_test_demo_secret_key_qrstuvwxyz',
      description: 'API key for development and testing',
      status: 'active',
      permissions: ['read', 'write'],
      createdBy: 'Super Admin',
      createdAt: '2024-01-10T09:15:00Z',
      lastUsed: '2024-01-19T16:45:00Z',
      expiresAt: '2024-12-31T23:59:59Z',
      usageCount: 8234
    },
    {
      id: 3,
      name: 'Read-Only API Key',
      key: 'pk_demo_readonly_key_456789123',
      secret: 'sk_demo_readonly_secret_key_mnopqrstuv',
      description: 'Limited access key for read-only operations',
      status: 'active',
      permissions: ['read'],
      createdBy: 'Super Admin',
      createdAt: '2024-01-05T11:20:00Z',
      lastUsed: '2024-01-18T12:30:00Z',
      expiresAt: null,
      usageCount: 5678
    },
    {
      id: 4,
      name: 'Webhook API Key',
      key: 'pk_demo_webhook_key_789123456',
      secret: 'sk_demo_webhook_secret_key_uvwxyzabcd',
      description: 'Specialized key for webhook integrations',
      status: 'inactive',
      permissions: ['read', 'write'],
      createdBy: 'Super Admin',
      createdAt: '2024-01-01T08:00:00Z',
      lastUsed: '2024-01-15T10:15:00Z',
      expiresAt: '2024-06-30T23:59:59Z',
      usageCount: 2341
    }
  ];

  useEffect(() => {
    // Simulate API call
    setTimeout(() => {
      setApiKeys(demoApiKeys);
      setLoading(false);
    }, 1000);
  }, []);

  const handleCreateKey = () => {
    if (!newKeyData.name.trim()) {
      toast.error('API key name is required');
      return;
    }

    const newKey = {
      id: apiKeys.length + 1,
      name: newKeyData.name,
      key: `pk_demo_new_key_${Date.now()}`,
      secret: `sk_demo_new_secret_${Date.now()}`,
      description: newKeyData.description,
      status: 'active',
      permissions: newKeyData.permissions,
      createdBy: 'Super Admin',
      createdAt: new Date().toISOString(),
      lastUsed: null,
      expiresAt: newKeyData.expiresAt || null,
      usageCount: 0
    };

    setApiKeys([...apiKeys, newKey]);
    setShowCreateModal(false);
    setNewKeyData({ name: '', description: '', permissions: [], expiresAt: '' });
    toast.success('API key created successfully');
  };

  const handleDeleteKey = () => {
    if (!selectedKey) return;
    
    setApiKeys(apiKeys.filter(key => key.id !== selectedKey.id));
    setShowDeleteModal(false);
    setSelectedKey(null);
    toast.success('API key deleted successfully');
  };

  const handleToggleStatus = (keyId) => {
    setApiKeys(apiKeys.map(key => 
      key.id === keyId 
        ? { ...key, status: key.status === 'active' ? 'inactive' : 'active' }
        : key
    ));
    toast.success('API key status updated');
  };

  const handleCopyToClipboard = (text, type) => {
    navigator.clipboard.writeText(text);
    toast.success(`${type} copied to clipboard`);
  };

  const handleRegenerateKey = (keyId) => {
    setApiKeys(apiKeys.map(key => 
      key.id === keyId 
        ? { 
            ...key, 
            key: `pk_demo_regenerated_${Date.now()}`,
            secret: `sk_demo_regenerated_${Date.now()}`
          }
        : key
    ));
    toast.success('API key regenerated successfully');
  };

  const filteredKeys = apiKeys.filter(key => {
    const matchesFilter = filter === 'all' || key.status === filter;
    const matchesSearch = key.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         key.description.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesFilter && matchesSearch;
  });

  const getStatusColor = (status) => {
    return status === 'active' ? 'text-green-600' : 'text-red-600';
  };

  const getStatusBg = (status) => {
    return status === 'active' ? 'bg-green-100' : 'bg-red-100';
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getPermissionBadge = (permission) => {
    const colors = {
      read: 'bg-blue-100 text-blue-800',
      write: 'bg-green-100 text-green-800',
      delete: 'bg-red-100 text-red-800'
    };
    return colors[permission] || 'bg-gray-100 text-gray-800';
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-6">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-1/4 mb-6"></div>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {[1, 2, 3].map(i => (
                <div key={i} className="bg-white rounded-lg shadow p-6">
                  <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2 mb-2"></div>
                  <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                <FaKey className="mr-3 text-primary" />
                API Keys Management
              </h1>
              <p className="text-gray-600 mt-2">
                Manage API keys for system integrations and third-party services
              </p>
            </div>
            <button
              onClick={() => setShowCreateModal(true)}
              className="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium flex items-center transition-colors duration-200"
            >
              <FaPlus className="mr-2" />
              Create New API Key
            </button>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-blue-100 rounded-lg">
                <FaKey className="text-blue-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total API Keys</p>
                <p className="text-2xl font-bold text-gray-900">{apiKeys.length}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-green-100 rounded-lg">
                <FaShieldAlt className="text-green-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Active Keys</p>
                <p className="text-2xl font-bold text-gray-900">
                  {apiKeys.filter(key => key.status === 'active').length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-yellow-100 rounded-lg">
                <FaClock className="text-yellow-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                <p className="text-2xl font-bold text-gray-900">
                  {apiKeys.filter(key => {
                    if (!key.expiresAt) return false;
                    const expiryDate = new Date(key.expiresAt);
                    const now = new Date();
                    const daysUntilExpiry = (expiryDate - now) / (1000 * 60 * 60 * 24);
                    return daysUntilExpiry <= 30 && daysUntilExpiry > 0;
                  }).length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-purple-100 rounded-lg">
                <FaUser className="text-purple-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Usage</p>
                <p className="text-2xl font-bold text-gray-900">
                  {apiKeys.reduce((sum, key) => sum + key.usageCount, 0).toLocaleString()}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters and Search */}
        <div className="bg-white rounded-lg shadow mb-6 p-6">
          <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div className="flex gap-4">
              <select
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
                className="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="all">All Keys</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            
            <div className="relative">
              <input
                type="text"
                placeholder="Search API keys..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-64 focus:ring-2 focus:ring-primary focus:border-transparent"
              />
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            </div>
          </div>
        </div>

        {/* API Keys List */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    API Key
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Permissions
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Last Used
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Usage
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredKeys.map((key) => (
                  <tr key={key.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{key.name}</div>
                        <div className="text-sm text-gray-500">{key.description}</div>
                        <div className="flex items-center mt-1">
                          <code className="text-xs bg-gray-100 px-2 py-1 rounded">
                            {key.key.substring(0, 20)}...
                          </code>
                          <button
                            onClick={() => handleCopyToClipboard(key.key, 'API Key')}
                            className="ml-2 text-gray-400 hover:text-gray-600"
                          >
                            <FaCopy size={12} />
                          </button>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBg(key.status)} ${getStatusColor(key.status)}`}>
                        {key.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex flex-wrap gap-1">
                        {key.permissions.map((permission) => (
                          <span
                            key={permission}
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPermissionBadge(permission)}`}
                          >
                            {permission}
                          </span>
                        ))}
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {formatDate(key.lastUsed)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {key.usageCount.toLocaleString()}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => {
                            setShowSecret(prev => ({ ...prev, [key.id]: !prev[key.id] }));
                          }}
                          className="text-gray-400 hover:text-gray-600"
                          title="View Secret"
                        >
                          {showSecret[key.id] ? <FaEyeSlash size={14} /> : <FaEye size={14} />}
                        </button>
                        
                        <button
                          onClick={() => handleRegenerateKey(key.id)}
                          className="text-blue-400 hover:text-blue-600"
                          title="Regenerate Key"
                        >
                          <FaRefresh size={14} />
                        </button>
                        
                        <button
                          onClick={() => handleToggleStatus(key.id)}
                          className={`${key.status === 'active' ? 'text-red-400 hover:text-red-600' : 'text-green-400 hover:text-green-600'}`}
                          title={key.status === 'active' ? 'Deactivate' : 'Activate'}
                        >
                          {key.status === 'active' ? <FaTimes size={14} /> : <FaCheck size={14} />}
                        </button>
                        
                        <button
                          onClick={() => {
                            setSelectedKey(key);
                            setShowDeleteModal(true);
                          }}
                          className="text-red-400 hover:text-red-600"
                          title="Delete Key"
                        >
                          <FaTrash size={14} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Secret Keys Modal */}
        {Object.keys(showSecret).map(keyId => {
          if (!showSecret[keyId]) return null;
          const key = apiKeys.find(k => k.id === parseInt(keyId));
          if (!key) return null;
          
          return (
            <div key={keyId} className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 className="text-lg font-semibold mb-4">Secret Key for {key.name}</h3>
                <div className="bg-gray-100 p-3 rounded mb-4">
                  <code className="text-sm break-all">{key.secret}</code>
                </div>
                <div className="flex justify-end space-x-3">
                  <button
                    onClick={() => handleCopyToClipboard(key.secret, 'Secret Key')}
                    className="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded"
                  >
                    Copy Secret
                  </button>
                  <button
                    onClick={() => setShowSecret(prev => ({ ...prev, [keyId]: false }))}
                    className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          );
        })}

        {/* Create API Key Modal */}
        {showCreateModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
              <h3 className="text-lg font-semibold mb-4">Create New API Key</h3>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Key Name *
                  </label>
                  <input
                    type="text"
                    value={newKeyData.name}
                    onChange={(e) => setNewKeyData({ ...newKeyData, name: e.target.value })}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
                    placeholder="Enter API key name"
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Description
                  </label>
                  <textarea
                    value={newKeyData.description}
                    onChange={(e) => setNewKeyData({ ...newKeyData, description: e.target.value })}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
                    rows="3"
                    placeholder="Enter description"
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Permissions
                  </label>
                  <div className="space-y-2">
                    {['read', 'write', 'delete'].map((permission) => (
                      <label key={permission} className="flex items-center">
                        <input
                          type="checkbox"
                          checked={newKeyData.permissions.includes(permission)}
                          onChange={(e) => {
                            if (e.target.checked) {
                              setNewKeyData({
                                ...newKeyData,
                                permissions: [...newKeyData.permissions, permission]
                              });
                            } else {
                              setNewKeyData({
                                ...newKeyData,
                                permissions: newKeyData.permissions.filter(p => p !== permission)
                              });
                            }
                          }}
                          className="mr-2"
                        />
                        <span className="text-sm capitalize">{permission}</span>
                      </label>
                    ))}
                  </div>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Expiration Date (Optional)
                  </label>
                  <input
                    type="datetime-local"
                    value={newKeyData.expiresAt}
                    onChange={(e) => setNewKeyData({ ...newKeyData, expiresAt: e.target.value })}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
                  />
                </div>
              </div>
              
              <div className="flex justify-end space-x-3 mt-6">
                <button
                  onClick={() => setShowCreateModal(false)}
                  className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded"
                >
                  Cancel
                </button>
                <button
                  onClick={handleCreateKey}
                  className="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded"
                >
                  Create API Key
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Delete Confirmation Modal */}
        {showDeleteModal && selectedKey && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
              <h3 className="text-lg font-semibold mb-4">Delete API Key</h3>
              <p className="text-gray-600 mb-6">
                Are you sure you want to delete the API key "{selectedKey.name}"? This action cannot be undone.
              </p>
              <div className="flex justify-end space-x-3">
                <button
                  onClick={() => setShowDeleteModal(false)}
                  className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded"
                >
                  Cancel
                </button>
                <button
                  onClick={handleDeleteKey}
                  className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default SuperAdminAPIKeysPage;
