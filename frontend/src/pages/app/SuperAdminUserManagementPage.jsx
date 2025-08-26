import React, { useState, useEffect } from 'react';
import {
  FiUsers, FiUserPlus, FiSearch, FiFilter, FiEdit, FiTrash2, FiEye,
  FiShield, FiUserCheck, FiUserX, FiMail, FiPhone, FiMapPin,
  FiCalendar, FiClock, FiDownload, FiRefreshCw, FiAlertCircle,
  FiCheck, FiX, FiMoreVertical, FiLock, FiUnlock
} from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const SuperAdminUserManagementPage = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [roleFilter, setRoleFilter] = useState('all');
  const [tenantFilter, setTenantFilter] = useState('all');
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [showBulkActions, setShowBulkActions] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [tenants, setTenants] = useState([]);
  const [showUserModal, setShowUserModal] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [userToDelete, setUserToDelete] = useState(null);

  const fetchUsers = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        page: currentPage,
        search: searchTerm,
        status: statusFilter !== 'all' ? statusFilter : undefined,
        role: roleFilter !== 'all' ? roleFilter : undefined,
        tenant: tenantFilter !== 'all' ? tenantFilter : undefined
      };
      
      const response = await superAdminAPI.getUsers(params);
      if (response.data.success) {
        setUsers(response.data.data.users);
        setTotalPages(response.data.data.totalPages);
      } else {
        setError('Failed to load users');
      }
    } catch (err) {
      setError('Error loading users');
      console.error('Users error:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchTenants = async () => {
    try {
      const response = await superAdminAPI.getTenants({ limit: 100 });
      if (response.data.success) {
        setTenants(response.data.data.tenants);
      }
    } catch (err) {
      console.error('Error loading tenants:', err);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, [currentPage, searchTerm, statusFilter, roleFilter, tenantFilter]);

  useEffect(() => {
    fetchTenants();
  }, []);

  const handleSearch = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    fetchUsers();
  };

  const handleUserAction = async (userId, action, data = {}) => {
    try {
      let response;
      switch (action) {
        case 'activate':
          response = await superAdminAPI.updateUser(userId, { status: 'active' });
          break;
        case 'suspend':
          response = await superAdminAPI.updateUser(userId, { status: 'suspended' });
          break;
        case 'delete':
          response = await superAdminAPI.deleteUser(userId);
          break;
        case 'update':
          response = await superAdminAPI.updateUser(userId, data);
          break;
        default:
          return;
      }

      if (response.data.success) {
        fetchUsers();
        setShowUserModal(false);
        setShowDeleteModal(false);
        setSelectedUser(null);
        setUserToDelete(null);
      } else {
        setError(`Failed to ${action} user`);
      }
    } catch (err) {
      setError(`Error ${action}ing user`);
      console.error(`User ${action} error:`, err);
    }
  };

  const handleBulkAction = async (action) => {
    if (selectedUsers.length === 0) return;

    try {
      const response = await superAdminAPI.bulkUserAction(selectedUsers, action);
      if (response.data.success) {
        setSelectedUsers([]);
        setShowBulkActions(false);
        fetchUsers();
      } else {
        setError(`Failed to ${action} users`);
      }
    } catch (err) {
      setError(`Error ${action}ing users`);
      console.error(`Bulk ${action} error:`, err);
    }
  };

  const handleSelectUser = (userId) => {
    setSelectedUsers(prev => 
      prev.includes(userId) 
        ? prev.filter(id => id !== userId)
        : [...prev, userId]
    );
  };

  const handleSelectAll = () => {
    if (selectedUsers.length === users.length) {
      setSelectedUsers([]);
    } else {
      setSelectedUsers(users.map(user => user.id));
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'suspended': return 'bg-red-100 text-red-800';
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getRoleColor = (role) => {
    switch (role) {
      case 'admin': return 'bg-purple-100 text-purple-800';
      case 'manager': return 'bg-blue-100 text-blue-800';
      case 'cashier': return 'bg-orange-100 text-orange-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading Users...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">User Management</h1>
            <p className="text-[#746354] mt-1">
              Manage all users across all tenants
            </p>
          </div>
          <div className="flex items-center space-x-4">
            <button 
              onClick={() => setShowUserModal(true)}
              className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              <FiUserPlus className="h-4 w-4 mr-2" />
              Add User
            </button>
            <button 
              onClick={fetchUsers}
              className="flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
            >
              <FiRefreshCw className="h-4 w-4 mr-2" />
              Refresh
            </button>
          </div>
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

      {/* Search and Filters */}
      <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
        <form onSubmit={handleSearch} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div className="relative">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#746354]" />
              <input
                type="text"
                placeholder="Search users..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
              />
            </div>
            
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
            >
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
              <option value="pending">Pending</option>
            </select>

            <select
              value={roleFilter}
              onChange={(e) => setRoleFilter(e.target.value)}
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
            >
              <option value="all">All Roles</option>
              <option value="admin">Admin</option>
              <option value="manager">Manager</option>
              <option value="cashier">Cashier</option>
            </select>

            <select
              value={tenantFilter}
              onChange={(e) => setTenantFilter(e.target.value)}
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
            >
              <option value="all">All Tenants</option>
              {tenants.map(tenant => (
                <option key={tenant.id} value={tenant.id}>{tenant.name}</option>
              ))}
            </select>

            <button
              type="submit"
              className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              Search
            </button>
          </div>
        </form>
      </div>

      {/* Bulk Actions */}
      {selectedUsers.length > 0 && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div className="flex items-center justify-between">
            <span className="text-blue-800">
              {selectedUsers.length} user(s) selected
            </span>
            <div className="flex items-center space-x-2">
              <button
                onClick={() => handleBulkAction('activate')}
                className="flex items-center px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700"
              >
                <FiUserCheck className="h-3 w-3 mr-1" />
                Activate
              </button>
              <button
                onClick={() => handleBulkAction('suspend')}
                className="flex items-center px-3 py-1 bg-yellow-600 text-white rounded text-sm hover:bg-yellow-700"
              >
                <FiUserX className="h-3 w-3 mr-1" />
                Suspend
              </button>
              <button
                onClick={() => handleBulkAction('delete')}
                className="flex items-center px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700"
              >
                <FiTrash2 className="h-3 w-3 mr-1" />
                Delete
              </button>
              <button
                onClick={() => setSelectedUsers([])}
                className="text-blue-600 hover:text-blue-800"
              >
                Clear
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Users Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left">
                  <input
                    type="checkbox"
                    checked={selectedUsers.length === users.length && users.length > 0}
                    onChange={handleSelectAll}
                    className="rounded border-gray-300 text-[#e41e5b] focus:ring-[#e41e5b]"
                  />
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  User
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Role
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Tenant
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Last Login
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {users.map((user) => (
                <tr key={user.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <input
                      type="checkbox"
                      checked={selectedUsers.includes(user.id)}
                      onChange={() => handleSelectUser(user.id)}
                      className="rounded border-gray-300 text-[#e41e5b] focus:ring-[#e41e5b]"
                    />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10">
                        <div className="h-10 w-10 rounded-full bg-[#e41e5b] flex items-center justify-center">
                          <span className="text-white font-medium">{user.name.charAt(0)}</span>
                        </div>
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-[#2c2c2c]">{user.name}</div>
                        <div className="text-sm text-[#746354]">{user.email}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getRoleColor(user.role)}`}>
                      {user.role}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-[#2c2c2c]">
                    {user.tenant_name || 'N/A'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(user.status)}`}>
                      {user.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-[#746354]">
                    {user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => {
                          setSelectedUser(user);
                          setShowUserModal(true);
                        }}
                        className="text-[#e41e5b] hover:text-[#9a0864]"
                        title="Edit User"
                      >
                        <FiEdit className="h-4 w-4" />
                      </button>
                      {user.status === 'active' ? (
                        <button
                          onClick={() => handleUserAction(user.id, 'suspend')}
                          className="text-yellow-600 hover:text-yellow-800"
                          title="Suspend User"
                        >
                          <FiLock className="h-4 w-4" />
                        </button>
                      ) : (
                        <button
                          onClick={() => handleUserAction(user.id, 'activate')}
                          className="text-green-600 hover:text-green-800"
                          title="Activate User"
                        >
                          <FiUnlock className="h-4 w-4" />
                        </button>
                      )}
                      <button
                        onClick={() => {
                          setUserToDelete(user);
                          setShowDeleteModal(true);
                        }}
                        className="text-red-600 hover:text-red-800"
                        title="Delete User"
                      >
                        <FiTrash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div className="flex-1 flex justify-between sm:hidden">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Previous
              </button>
              <button
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Next
              </button>
            </div>
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p className="text-sm text-[#746354]">
                  Showing page <span className="font-medium">{currentPage}</span> of{' '}
                  <span className="font-medium">{totalPages}</span>
                </p>
              </div>
              <div>
                <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <button
                    onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                    disabled={currentPage === 1}
                    className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  >
                    Previous
                  </button>
                  {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                    const page = Math.max(1, Math.min(totalPages - 4, currentPage - 2)) + i;
                    return (
                      <button
                        key={page}
                        onClick={() => setCurrentPage(page)}
                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                          page === currentPage
                            ? 'z-10 bg-[#e41e5b] border-[#e41e5b] text-white'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        }`}
                      >
                        {page}
                      </button>
                    );
                  })}
                  <button
                    onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                    disabled={currentPage === totalPages}
                    className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  >
                    Next
                  </button>
                </nav>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* User Modal */}
      {showUserModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-[#2c2c2c] mb-4">
                {selectedUser ? 'Edit User' : 'Add New User'}
              </h3>
              <form className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-[#746354]">Name</label>
                  <input
                    type="text"
                    defaultValue={selectedUser?.name || ''}
                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#746354]">Email</label>
                  <input
                    type="email"
                    defaultValue={selectedUser?.email || ''}
                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#746354]">Role</label>
                  <select className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#e41e5b] focus:border-[#e41e5b]">
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="cashier">Cashier</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-[#746354]">Status</label>
                  <select className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#e41e5b] focus:border-[#e41e5b]">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="pending">Pending</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => setShowUserModal(false)}
                    className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-[#e41e5b] text-white rounded-md hover:bg-[#9a0864]"
                  >
                    {selectedUser ? 'Update' : 'Create'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div className="mt-3 text-center">
              <FiAlertCircle className="mx-auto h-12 w-12 text-red-500 mb-4" />
              <h3 className="text-lg font-medium text-[#2c2c2c] mb-2">Delete User</h3>
              <p className="text-sm text-[#746354] mb-4">
                Are you sure you want to delete {userToDelete?.name}? This action cannot be undone.
              </p>
              <div className="flex justify-center space-x-3">
                <button
                  onClick={() => setShowDeleteModal(false)}
                  className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                >
                  Cancel
                </button>
                <button
                  onClick={() => handleUserAction(userToDelete.id, 'delete')}
                  className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SuperAdminUserManagementPage;
