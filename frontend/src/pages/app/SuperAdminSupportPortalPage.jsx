import React, { useState, useEffect } from 'react';
import { 
  FiSearch, 
  FiFilter, 
  FiBookOpen, 
  FiMessageSquare, 
  FiPlus, 
  FiHelpCircle,
  FiClock,
  FiCheckCircle,
  FiAlertCircle,
  FiEdit,
  FiTrash2,
  FiEye
} from 'react-icons/fi';

const SuperAdminSupportPortalPage = () => {
  const [activeTab, setActiveTab] = useState('tickets');
  const [tickets, setTickets] = useState([]);
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState(null);
  const [showTicketModal, setShowTicketModal] = useState(false);
  const [filters, setFilters] = useState({
    status: '',
    priority: '',
    category: ''
  });

  useEffect(() => {
    if (activeTab === 'tickets') {
      loadTickets();
    } else if (activeTab === 'knowledgebase') {
      loadKnowledgebase();
    }
  }, [activeTab, filters]);

  const loadTickets = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        ...filters,
        limit: 50
      });

      const response = await fetch(`/api/support-portal.php/tickets?${params}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setTickets(data.data.tickets);
      }
    } catch (error) {
      console.error('Error loading tickets:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadKnowledgebase = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/support-portal.php/knowledgebase?limit=50');
      const data = await response.json();
      
      if (data.success) {
        setArticles(data.data.articles);
      }
    } catch (error) {
      console.error('Error loading knowledgebase:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateTicketStatus = async (ticketId, status) => {
    try {
      const response = await fetch('/api/support-portal.php/tickets', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          ticket_id: ticketId,
          status: status
        })
      });

      const data = await response.json();
      if (data.success) {
        loadTickets();
      }
    } catch (error) {
      console.error('Error updating ticket:', error);
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'urgent': return 'text-red-600 bg-red-50';
      case 'high': return 'text-orange-600 bg-orange-50';
      case 'medium': return 'text-yellow-600 bg-yellow-50';
      case 'low': return 'text-green-600 bg-green-50';
      default: return 'text-gray-600 bg-gray-50';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'open': return 'text-blue-600 bg-blue-50';
      case 'in_progress': return 'text-purple-600 bg-purple-50';
      case 'waiting_for_customer': return 'text-yellow-600 bg-yellow-50';
      case 'resolved': return 'text-green-600 bg-green-50';
      case 'closed': return 'text-gray-600 bg-gray-50';
      default: return 'text-gray-600 bg-gray-50';
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Support Portal Management</h1>
        <p className="text-gray-600">Manage support tickets, knowledgebase articles, and customer interactions</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-blue-100 rounded-lg">
              <FiMessageSquare className="h-6 w-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Tickets</p>
              <p className="text-2xl font-bold text-gray-900">{tickets.length}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-yellow-100 rounded-lg">
              <FiAlertCircle className="h-6 w-6 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Open Tickets</p>
              <p className="text-2xl font-bold text-gray-900">
                {tickets.filter(t => t.status === 'open').length}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-green-100 rounded-lg">
              <FiCheckCircle className="h-6 w-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Resolved</p>
              <p className="text-2xl font-bold text-gray-900">
                {tickets.filter(t => t.status === 'resolved').length}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center">
            <div className="p-2 bg-purple-100 rounded-lg">
              <FiBookOpen className="h-6 w-6 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Articles</p>
              <p className="text-2xl font-bold text-gray-900">{articles.length}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="mb-6">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8">
            <button
              onClick={() => setActiveTab('tickets')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'tickets'
                  ? 'border-primary text-primary'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <FiMessageSquare className="inline mr-2 h-4 w-4" />
              Support Tickets
            </button>
            <button
              onClick={() => setActiveTab('knowledgebase')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'knowledgebase'
                  ? 'border-primary text-primary'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <FiBookOpen className="inline mr-2 h-4 w-4" />
              Knowledge Base
            </button>
          </nav>
        </div>
      </div>

      {/* Content */}
      {activeTab === 'tickets' && (
        <div className="bg-white rounded-lg shadow-sm border">
          {/* Filters */}
          <div className="p-6 border-b border-gray-200">
            <div className="flex flex-wrap items-center gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  value={filters.status}
                  onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value }))}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">All Status</option>
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="waiting_for_customer">Waiting for Customer</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select
                  value={filters.priority}
                  onChange={(e) => setFilters(prev => ({ ...prev, priority: e.target.value }))}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">All Priorities</option>
                  <option value="urgent">Urgent</option>
                  <option value="high">High</option>
                  <option value="medium">Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select
                  value={filters.category}
                  onChange={(e) => setFilters(prev => ({ ...prev, category: e.target.value }))}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">All Categories</option>
                  <option value="general">General</option>
                  <option value="technical">Technical</option>
                  <option value="billing">Billing</option>
                  <option value="feature">Feature Request</option>
                  <option value="bug">Bug Report</option>
                  <option value="account">Account</option>
                </select>
              </div>
            </div>
          </div>

          {/* Tickets List */}
          <div className="divide-y divide-gray-200">
            {loading ? (
              <div className="p-6">
                <div className="animate-pulse space-y-4">
                  {[...Array(5)].map((_, i) => (
                    <div key={i} className="border rounded-lg p-4">
                      <div className="h-4 bg-gray-200 rounded mb-2"></div>
                      <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                    </div>
                  ))}
                </div>
              </div>
            ) : tickets.length > 0 ? (
              tickets.map((ticket) => (
                <div key={ticket.id} className="p-6 hover:bg-gray-50 transition-colors">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center space-x-3 mb-2">
                        <h3 className="text-lg font-medium text-gray-900">
                          {ticket.subject}
                        </h3>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${getPriorityColor(ticket.priority)}`}>
                          {ticket.priority}
                        </span>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(ticket.status)}`}>
                          {ticket.status.replace('_', ' ')}
                        </span>
                      </div>
                      
                      <p className="text-gray-600 mb-3">{ticket.message}</p>
                      
                      <div className="flex items-center space-x-4 text-sm text-gray-500 mb-3">
                        <span className="flex items-center">
                          {/* FiUser was removed from imports, so this line is commented out */}
                          {/* <FiUser className="mr-1 h-4 w-4" /> */}
                          {ticket.first_name} {ticket.last_name}
                        </span>
                        <span className="flex items-center">
                          {/* FiMail was removed from imports, so this line is commented out */}
                          {/* <FiMail className="mr-1 h-4 w-4" /> */}
                          {ticket.email}
                        </span>
                        <span className="flex items-center">
                          {/* FiTag was removed from imports, so this line is commented out */}
                          {/* <FiTag className="mr-1 h-4 w-4" /> */}
                          {ticket.category}
                        </span>
                        <span className="flex items-center">
                          <FiClock className="mr-1 h-4 w-4" />
                          {formatDate(ticket.created_at)}
                        </span>
                      </div>

                      <div className="flex items-center space-x-2">
                        <select
                          value={ticket.status}
                          onChange={(e) => updateTicketStatus(ticket.id, e.target.value)}
                          className="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                          <option value="open">Open</option>
                          <option value="in_progress">In Progress</option>
                          <option value="waiting_for_customer">Waiting for Customer</option>
                          <option value="resolved">Resolved</option>
                          <option value="closed">Closed</option>
                        </select>
                        
                        <button
                          onClick={() => {
                            setSelectedTicket(ticket);
                            setShowTicketModal(true);
                          }}
                          className="text-primary hover:text-primary/80 text-sm font-medium transition-colors"
                        >
                          View Details
                        </button>
                      </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                      <span className="text-sm text-gray-500">#{ticket.ticket_number}</span>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-12">
                <FiMessageSquare className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No tickets found</h3>
                <p className="text-gray-500">No support tickets match your current filters.</p>
              </div>
            )}
          </div>
        </div>
      )}

      {activeTab === 'knowledgebase' && (
        <div className="bg-white rounded-lg shadow-sm border">
          <div className="p-6 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold text-gray-900">Knowledge Base Articles</h2>
              <button className="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                <FiEdit className="inline mr-2 h-4 w-4" />
                Create Article
              </button>
            </div>
          </div>
          
          <div className="divide-y divide-gray-200">
            {loading ? (
              <div className="p-6">
                <div className="animate-pulse space-y-4">
                  {[...Array(5)].map((_, i) => (
                    <div key={i} className="border rounded-lg p-4">
                      <div className="h-4 bg-gray-200 rounded mb-2"></div>
                      <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                    </div>
                  ))}
                </div>
              </div>
            ) : articles.length > 0 ? (
              articles.map((article) => (
                <div key={article.id} className="p-6 hover:bg-gray-50 transition-colors">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center space-x-2 mb-2">
                        <span className="text-sm text-gray-500">{article.category_name}</span>
                        {article.featured && (
                          <span className="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                            Featured
                          </span>
                        )}
                      </div>
                      
                      <h3 className="text-lg font-medium text-gray-900 mb-2">
                        {article.title}
                      </h3>
                      
                      <p className="text-gray-600 mb-3">
                        {article.excerpt || article.content.substring(0, 200)}...
                      </p>
                      
                      <div className="flex items-center space-x-4 text-sm text-gray-500">
                        <span>Views: {article.view_count || 0}</span>
                        <span>Helpful: {article.helpful_count || 0}</span>
                        <span>Created: {formatDate(article.created_at)}</span>
                      </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                      <button className="text-primary hover:text-primary/80 transition-colors">
                        <FiEye className="h-4 w-4" />
                      </button>
                      <button className="text-gray-400 hover:text-gray-600 transition-colors">
                        <FiEdit className="h-4 w-4" />
                      </button>
                      <button className="text-gray-400 hover:text-red-600 transition-colors">
                        <FiTrash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-12">
                <FiBookOpen className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No articles found</h3>
                <p className="text-gray-500">No knowledge base articles available.</p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Ticket Detail Modal */}
      {showTicketModal && selectedTicket && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-gray-200">
              <h2 className="text-xl font-semibold text-gray-900">
                Ticket #{selectedTicket.ticket_number}
              </h2>
              <button
                onClick={() => setShowTicketModal(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                {/* FiX was removed from imports, so this line is commented out */}
                {/* <FiX className="h-6 w-6" /> */}
              </button>
            </div>
            
            <div className="p-6">
              <div className="mb-6">
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  {selectedTicket.subject}
                </h3>
                <p className="text-gray-600">{selectedTicket.message}</p>
              </div>
              
              <div className="grid grid-cols-2 gap-4 mb-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                  <p className="text-gray-900">{selectedTicket.first_name} {selectedTicket.last_name}</p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <p className="text-gray-900">{selectedTicket.email}</p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                  <p className="text-gray-900">{selectedTicket.category}</p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Created</label>
                  <p className="text-gray-900">{formatDate(selectedTicket.created_at)}</p>
                </div>
              </div>
              
              <div className="flex items-center justify-end space-x-3">
                <button
                  onClick={() => setShowTicketModal(false)}
                  className="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                >
                  Close
                </button>
                <button className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                  Reply to Customer
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SuperAdminSupportPortalPage;
