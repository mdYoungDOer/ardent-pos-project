import React, { useState, useEffect } from 'react';
import { FiMessageSquare, FiClock, FiUser, FiMail, FiPhone, FiSearch, FiFilter, FiEye, FiSend, FiCheckCircle, FiXCircle, FiAlertCircle } from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const SupportTicketManagementPage = () => {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [priorityFilter, setPriorityFilter] = useState('all');
  const [selectedTicket, setSelectedTicket] = useState(null);
  const [showTicketModal, setShowTicketModal] = useState(false);
  const [showReplyModal, setShowReplyModal] = useState(false);
  const [replyContent, setReplyContent] = useState('');
  const [stats, setStats] = useState({
    total: 0,
    open: 0,
    in_progress: 0,
    resolved: 0,
    closed: 0
  });

  // Load data on component mount
  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    setError('');
    try {
      const [ticketsResponse, statsResponse] = await Promise.all([
        superAdminAPI.getSupportTickets(),
        superAdminAPI.getSupportTicketStats()
      ]);
      
      setTickets(ticketsResponse.data?.data?.tickets || []);
      setStats(statsResponse.data?.data || {
        total: 0,
        open: 0,
        in_progress: 0,
        resolved: 0,
        closed: 0
      });
    } catch (err) {
      setError('Failed to load data. Please try again.');
      console.error('Error loading data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };

  const handleStatusFilter = (e) => {
    setStatusFilter(e.target.value);
  };

  const handlePriorityFilter = (e) => {
    setPriorityFilter(e.target.value);
  };

  const filteredTickets = () => {
    let filtered = tickets;
    
    if (searchTerm) {
      filtered = filtered.filter(ticket => 
        ticket.subject.toLowerCase().includes(searchTerm.toLowerCase()) ||
        ticket.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
        ticket.customer_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        ticket.customer_email.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (statusFilter !== 'all') {
      filtered = filtered.filter(ticket => ticket.status === statusFilter);
    }

    if (priorityFilter !== 'all') {
      filtered = filtered.filter(ticket => ticket.priority === priorityFilter);
    }

    return filtered;
  };

  const openTicketModal = async (ticketId) => {
    try {
      const response = await superAdminAPI.getSupportTicket({ id: ticketId });
      setSelectedTicket(response.data?.data?.ticket);
      setShowTicketModal(true);
    } catch (err) {
      setError('Failed to load ticket details.');
      console.error('Error loading ticket:', err);
    }
  };

  const closeTicketModal = () => {
    setShowTicketModal(false);
    setSelectedTicket(null);
  };

  const openReplyModal = (ticket) => {
    setSelectedTicket(ticket);
    setReplyContent('');
    setShowReplyModal(true);
  };

  const closeReplyModal = () => {
    setShowReplyModal(false);
    setSelectedTicket(null);
    setReplyContent('');
  };

  const handleReplySubmit = async (e) => {
    e.preventDefault();
    if (!replyContent.trim()) return;

    setLoading(true);
    setError('');

    try {
      await superAdminAPI.addSupportReply({
        ticket_id: selectedTicket.id,
        content: replyContent,
        is_admin_reply: true
      });
      
      closeReplyModal();
      loadData(); // Reload to get updated ticket with new reply
    } catch (err) {
      setError('Failed to send reply. Please try again.');
      console.error('Error sending reply:', err);
    } finally {
      setLoading(false);
    }
  };

  const updateTicketStatus = async (ticketId, newStatus) => {
    setLoading(true);
    setError('');

    try {
      await superAdminAPI.updateSupportTicket({
        id: ticketId,
        status: newStatus
      });
      loadData();
    } catch (err) {
      setError('Failed to update ticket status.');
      console.error('Error updating ticket:', err);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'open': return 'bg-blue-100 text-blue-800';
      case 'in_progress': return 'bg-yellow-100 text-yellow-800';
      case 'resolved': return 'bg-green-100 text-green-800';
      case 'closed': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'low': return 'bg-green-100 text-green-800';
      case 'medium': return 'bg-yellow-100 text-yellow-800';
      case 'high': return 'bg-orange-100 text-orange-800';
      case 'urgent': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'open': return <FiAlertCircle className="inline mr-1" />;
      case 'in_progress': return <FiClock className="inline mr-1" />;
      case 'resolved': return <FiCheckCircle className="inline mr-1" />;
      case 'closed': return <FiXCircle className="inline mr-1" />;
      default: return <FiMessageSquare className="inline mr-1" />;
    }
  };

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Support Ticket Management</h1>
          <p className="text-gray-600">Manage and respond to customer support tickets</p>
        </div>

        {/* Error Message */}
        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-red-800">{error}</p>
          </div>
        )}

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center">
              <div className="p-2 bg-blue-100 rounded-lg">
                <FiMessageSquare className="h-6 w-6 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Tickets</p>
                <p className="text-2xl font-semibold text-gray-900">{stats.total}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center">
              <div className="p-2 bg-blue-100 rounded-lg">
                <FiAlertCircle className="h-6 w-6 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Open</p>
                <p className="text-2xl font-semibold text-blue-600">{stats.open}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center">
              <div className="p-2 bg-yellow-100 rounded-lg">
                <FiClock className="h-6 w-6 text-yellow-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">In Progress</p>
                <p className="text-2xl font-semibold text-yellow-600">{stats.in_progress}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center">
              <div className="p-2 bg-green-100 rounded-lg">
                <FiCheckCircle className="h-6 w-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Resolved</p>
                <p className="text-2xl font-semibold text-green-600">{stats.resolved}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="flex items-center">
              <div className="p-2 bg-gray-100 rounded-lg">
                <FiXCircle className="h-6 w-6 text-gray-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Closed</p>
                <p className="text-2xl font-semibold text-gray-600">{stats.closed}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Search and Filter Bar */}
        <div className="mb-6 flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search tickets..."
              value={searchTerm}
              onChange={handleSearch}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
            />
          </div>
          
          <div className="relative">
            <FiFilter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <select
              value={statusFilter}
              onChange={handleStatusFilter}
              className="pl-10 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>

          <div className="relative">
            <FiFilter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <select
              value={priorityFilter}
              onChange={handlePriorityFilter}
              className="pl-10 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
            >
              <option value="all">All Priority</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
        </div>

        {/* Tickets Table */}
        <div className="bg-white rounded-lg shadow">
          {loading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-e41e5b mx-auto"></div>
              <p className="mt-2 text-gray-600">Loading...</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {filteredTickets().map((ticket) => (
                    <tr key={ticket.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900 max-w-xs truncate">
                          #{ticket.id} - {ticket.subject}
                        </div>
                        <div className="text-sm text-gray-500 max-w-xs truncate">
                          {ticket.description}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{ticket.customer_name}</div>
                        <div className="text-sm text-gray-500">{ticket.customer_email}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(ticket.status)}`}>
                          {getStatusIcon(ticket.status)}
                          {ticket.status.replace('_', ' ')}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPriorityColor(ticket.priority)}`}>
                          {ticket.priority}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {new Date(ticket.created_at).toLocaleDateString()}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {new Date(ticket.updated_at).toLocaleDateString()}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button
                          onClick={() => openTicketModal(ticket.id)}
                          className="text-e41e5b hover:text-9a0864 mr-3"
                          title="View Details"
                        >
                          <FiEye className="inline" />
                        </button>
                                                 <button
                           onClick={() => openReplyModal(ticket)}
                           className="text-blue-600 hover:text-blue-900 mr-3"
                           title="Reply"
                         >
                           <FiSend className="inline" />
                         </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Ticket Detail Modal */}
        {showTicketModal && selectedTicket && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
              <div className="flex justify-between items-start mb-4">
                <h2 className="text-xl font-semibold">
                  Ticket #{selectedTicket.id} - {selectedTicket.subject}
                </h2>
                <button
                  onClick={closeTicketModal}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <FiXCircle className="h-6 w-6" />
                </button>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                  <h3 className="text-lg font-medium mb-2">Customer Information</h3>
                  <div className="bg-gray-50 p-4 rounded-lg">
                    <div className="flex items-center mb-2">
                      <FiUser className="mr-2 text-gray-400" />
                      <span className="font-medium">{selectedTicket.customer_name}</span>
                    </div>
                    <div className="flex items-center mb-2">
                      <FiMail className="mr-2 text-gray-400" />
                      <span>{selectedTicket.customer_email}</span>
                    </div>
                    {selectedTicket.customer_phone && (
                      <div className="flex items-center">
                        <FiPhone className="mr-2 text-gray-400" />
                        <span>{selectedTicket.customer_phone}</span>
                      </div>
                    )}
                  </div>
                </div>
                
                <div>
                  <h3 className="text-lg font-medium mb-2">Ticket Information</h3>
                  <div className="bg-gray-50 p-4 rounded-lg">
                    <div className="flex justify-between mb-2">
                      <span className="font-medium">Status:</span>
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(selectedTicket.status)}`}>
                        {getStatusIcon(selectedTicket.status)}
                        {selectedTicket.status.replace('_', ' ')}
                      </span>
                    </div>
                    <div className="flex justify-between mb-2">
                      <span className="font-medium">Priority:</span>
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPriorityColor(selectedTicket.priority)}`}>
                        {selectedTicket.priority}
                      </span>
                    </div>
                    <div className="flex justify-between mb-2">
                      <span className="font-medium">Created:</span>
                      <span>{new Date(selectedTicket.created_at).toLocaleString()}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="font-medium">Updated:</span>
                      <span>{new Date(selectedTicket.updated_at).toLocaleString()}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="mb-6">
                <h3 className="text-lg font-medium mb-2">Description</h3>
                <div className="bg-gray-50 p-4 rounded-lg">
                  <p className="whitespace-pre-wrap">{selectedTicket.description}</p>
                </div>
              </div>
              
              {selectedTicket.replies && selectedTicket.replies.length > 0 && (
                <div className="mb-6">
                  <h3 className="text-lg font-medium mb-2">Replies</h3>
                  <div className="space-y-4">
                    {selectedTicket.replies.map((reply, index) => (
                      <div key={index} className={`p-4 rounded-lg ${reply.is_admin_reply ? 'bg-blue-50 border-l-4 border-blue-400' : 'bg-gray-50 border-l-4 border-gray-400'}`}>
                        <div className="flex justify-between items-start mb-2">
                          <span className="font-medium">
                            {reply.is_admin_reply ? 'Admin' : selectedTicket.customer_name}
                          </span>
                          <span className="text-sm text-gray-500">
                            {new Date(reply.created_at).toLocaleString()}
                          </span>
                        </div>
                        <p className="whitespace-pre-wrap">{reply.content}</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              
              <div className="flex justify-between items-center">
                <div className="flex space-x-2">
                  <select
                    value={selectedTicket.status}
                    onChange={(e) => updateTicketStatus(selectedTicket.id, e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
                  >
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                  </select>
                </div>
                
                                 <button
                   onClick={() => openReplyModal(selectedTicket)}
                   className="px-4 py-2 bg-e41e5b text-white rounded-lg hover:bg-9a0864"
                 >
                   <FiSend className="inline mr-2" />
                   Reply
                 </button>
              </div>
            </div>
          </div>
        )}

        {/* Reply Modal */}
        {showReplyModal && selectedTicket && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 w-full max-w-2xl">
              <div className="flex justify-between items-start mb-4">
                <h2 className="text-xl font-semibold">
                  Reply to Ticket #{selectedTicket.id}
                </h2>
                <button
                  onClick={closeReplyModal}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <FiXCircle className="h-6 w-6" />
                </button>
              </div>
              
              <form onSubmit={handleReplySubmit}>
                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-2">Reply</label>
                  <textarea
                    value={replyContent}
                    onChange={(e) => setReplyContent(e.target.value)}
                    required
                    rows="6"
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
                    placeholder="Enter your reply..."
                  />
                </div>
                
                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={closeReplyModal}
                    className="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading || !replyContent.trim()}
                    className="px-4 py-2 bg-e41e5b text-white rounded-lg hover:bg-9a0864 disabled:opacity-50"
                  >
                    {loading ? 'Sending...' : 'Send Reply'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default SupportTicketManagementPage;
