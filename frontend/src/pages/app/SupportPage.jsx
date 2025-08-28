import React, { useState, useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { supportAPI } from '../../services/api';
import {
  FiSearch,
  FiBookOpen,
  FiMessageSquare,
  FiPlus,
  FiFilter,
  FiClock,
  FiUser,
  FiTag,
  FiEye,
  FiThumbsUp,
  FiX,
  FiHelpCircle,
  FiCheckCircle,
  FiAlertCircle,
  FiArrowRight
} from 'react-icons/fi';
import TicketModal from '../../components/support/TicketModal';

const SupportPage = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('knowledgebase');
  const [articles, setArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [showTicketModal, setShowTicketModal] = useState(false);
  const [selectedArticle, setSelectedArticle] = useState(null);
  const [showArticleModal, setShowArticleModal] = useState(false);
  const [filteredArticles, setFilteredArticles] = useState([]);

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    filterArticles();
  }, [articles, searchTerm, selectedCategory]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [articlesResponse, categoriesResponse, ticketsResponse] = await Promise.all([
        supportAPI.getKnowledgebase(),
        supportAPI.getCategories(),
        supportAPI.getTickets()
      ]);

      // Handle the data structure correctly
      const articlesData = articlesResponse.data?.articles || articlesResponse.data || [];
      const categoriesData = categoriesResponse.data?.categories || categoriesResponse.data || [];
      const ticketsData = ticketsResponse.data?.tickets || ticketsResponse.data || [];

      setArticles(Array.isArray(articlesData) ? articlesData : []);
      setCategories(Array.isArray(categoriesData) ? categoriesData : []);
      setTickets(Array.isArray(ticketsData) ? ticketsData : []);
      setFilteredArticles(Array.isArray(articlesData) ? articlesData : []);
    } catch (error) {
      console.error('Error loading support data:', error);
      setArticles([]);
      setCategories([]);
      setTickets([]);
      setFilteredArticles([]);
    } finally {
      setLoading(false);
    }
  };

  const filterArticles = () => {
    let filtered = articles;

    if (searchTerm) {
      filtered = filtered.filter(article =>
        article.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        article.content.toLowerCase().includes(searchTerm.toLowerCase()) ||
        article.tags?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (selectedCategory !== 'all') {
      filtered = filtered.filter(article => article.category_id == selectedCategory);
    }

    setFilteredArticles(filtered);
  };

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };

  const handleCategoryChange = (categoryId) => {
    setSelectedCategory(categoryId);
  };

  const handleArticleClick = async (articleId) => {
    try {
      const response = await supportAPI.getKnowledgebaseArticle(articleId);
      if (response.data) {
        setSelectedArticle(response.data);
        setShowArticleModal(true);
      }
    } catch (error) {
      console.error('Error loading article:', error);
    }
  };

  const getCategoryName = (categoryId) => {
    const category = categories.find(cat => cat.id === categoryId);
    return category ? category.name : 'Unknown';
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'open': return 'text-blue-600 bg-blue-50 border-blue-200';
      case 'in_progress': return 'text-purple-600 bg-purple-50 border-purple-200';
      case 'waiting_for_customer': return 'text-yellow-600 bg-yellow-50 border-yellow-200';
      case 'resolved': return 'text-green-600 bg-green-50 border-green-200';
      case 'closed': return 'text-gray-600 bg-gray-50 border-gray-200';
      default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'urgent': return 'text-red-600 bg-red-50 border-red-200';
      case 'high': return 'text-orange-600 bg-orange-50 border-orange-200';
      case 'medium': return 'text-yellow-600 bg-yellow-50 border-yellow-200';
      case 'low': return 'text-green-600 bg-green-50 border-green-200';
      default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
  };

  if (loading) {
    return (
      <div className="p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-gray-200 rounded w-1/4"></div>
          <div className="h-4 bg-gray-200 rounded w-1/2"></div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {[...Array(6)].map((_, i) => (
              <div key={i} className="border rounded-lg p-4">
                <div className="h-4 bg-gray-200 rounded mb-2"></div>
                <div className="h-3 bg-gray-200 rounded w-2/3"></div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="mb-6" style={{ paddingTop: '50px' }}>
        <h1 className="text-2xl font-bold text-gray-900 mb-2">How can we help you?</h1>
        <p className="text-gray-600">Find answers, create tickets, and get help with your POS system</p>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
          <div className="flex items-center mb-4">
            <FiBookOpen className="h-8 w-8 text-primary mr-3" />
            <h3 className="text-xl font-semibold">Knowledge Base</h3>
          </div>
          <p className="text-gray-600 mb-4">
            Browse our comprehensive collection of guides, tutorials, and FAQs
          </p>
          <button
            onClick={() => setActiveTab('knowledgebase')}
            className="text-primary hover:text-accent-1 font-medium flex items-center"
          >
            Browse Articles <FiArrowRight className="ml-2 h-4 w-4" />
          </button>
        </div>

        <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
          <div className="flex items-center mb-4">
            <FiMessageSquare className="h-8 w-8 text-primary mr-3" />
            <h3 className="text-xl font-semibold">Support Tickets</h3>
          </div>
          <p className="text-gray-600 mb-4">
            Create a ticket for personalized support from our team
          </p>
          <button
            onClick={() => setShowTicketModal(true)}
            className="text-primary hover:text-accent-1 font-medium flex items-center"
          >
            Create Ticket <FiPlus className="ml-2 h-4 w-4" />
          </button>
        </div>

        <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
          <div className="flex items-center mb-4">
            <FiHelpCircle className="h-8 w-8 text-primary mr-3" />
            <h3 className="text-xl font-semibold">My Tickets</h3>
          </div>
          <p className="text-gray-600 mb-4">
            View and manage your existing support tickets
          </p>
          <button
            onClick={() => setActiveTab('tickets')}
            className="text-primary hover:text-accent-1 font-medium flex items-center"
          >
            View Tickets ({tickets.length}) <FiEye className="ml-2 h-4 w-4" />
          </button>
        </div>
      </div>

      {/* Tabs */}
      <div className="mb-8">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8">
            <button
              onClick={() => setActiveTab('knowledgebase')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'knowledgebase'
                  ? 'border-primary text-primary'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <FiBookOpen className="inline h-4 w-4 mr-2" />
              Knowledge Base
            </button>
            <button
              onClick={() => setActiveTab('tickets')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'tickets'
                  ? 'border-primary text-primary'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <FiMessageSquare className="inline h-4 w-4 mr-2" />
              My Tickets ({tickets.length})
            </button>
          </nav>
        </div>
      </div>

      {/* Knowledge Base Tab */}
      {activeTab === 'knowledgebase' && (
        <div>
          {/* Search Bar */}
          <div className="mb-6">
            <div className="relative max-w-md">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
              <input
                type="text"
                placeholder="Search articles..."
                value={searchTerm}
                onChange={handleSearch}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>
          </div>

          {/* Category Filter */}
          <div className="flex flex-wrap gap-2 mb-6">
            <button
              onClick={() => handleCategoryChange('all')}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                selectedCategory === 'all'
                  ? 'bg-primary text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              }`}
            >
              All Categories
            </button>
            {categories.map((category) => (
              <button
                key={category.id}
                onClick={() => handleCategoryChange(category.id.toString())}
                className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                  selectedCategory === category.id.toString()
                    ? 'bg-primary text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                }`}
              >
                {category.name}
              </button>
            ))}
          </div>

          {/* Articles Grid */}
          {filteredArticles.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredArticles.map((article) => (
                <div
                  key={article.id}
                  className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200 hover:-translate-y-1 cursor-pointer"
                  onClick={() => handleArticleClick(article.id)}
                >
                  <div className="flex items-center mb-3">
                    <span className="text-sm font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                      {getCategoryName(article.category_id)}
                    </span>
                  </div>
                  
                  <h3 className="text-lg font-semibold text-gray-900 mb-3 hover:text-primary transition-colors line-clamp-2">
                    {article.title}
                  </h3>
                  
                  <p className="text-gray-600 text-sm mb-4 line-clamp-3">
                    {article.content.substring(0, 120)}...
                  </p>
                  
                  <div className="flex items-center justify-between text-xs text-gray-500">
                    <div className="flex items-center space-x-3">
                      <span className="flex items-center">
                        <FiEye className="h-3 w-3 mr-1" />
                        {article.view_count || 0}
                      </span>
                      <span className="flex items-center">
                        <FiThumbsUp className="h-3 w-3 mr-1" />
                        {article.helpful_count || 0}
                      </span>
                    </div>
                    <span className="flex items-center">
                      <FiClock className="h-3 w-3 mr-1" />
                      {new Date(article.created_at).toLocaleDateString()}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <FiSearch className="h-16 w-16 text-gray-300 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">No articles found</h3>
              <p className="text-gray-600">
                {searchTerm ? `No articles match "${searchTerm}"` : 'No articles available in this category'}
              </p>
            </div>
          )}
        </div>
      )}

      {/* Tickets Tab */}
      {activeTab === 'tickets' && (
        <div className="bg-white rounded-lg shadow-sm border">
          <div className="p-6 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold text-gray-900">My Support Tickets</h2>
              <button
                onClick={() => setShowTicketModal(true)}
                className="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors"
              >
                <FiPlus className="inline mr-2 h-4 w-4" />
                New Ticket
              </button>
            </div>
          </div>
          
          <div className="divide-y divide-gray-200">
            {tickets.length > 0 ? (
              tickets.map((ticket) => (
                <div key={ticket.id} className="p-6 hover:bg-gray-50 transition-colors">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center space-x-3 mb-2">
                        <h3 className="text-lg font-medium text-gray-900">
                          {ticket.subject}
                        </h3>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full border ${getPriorityColor(ticket.priority)}`}>
                          {ticket.priority}
                        </span>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full border ${getStatusColor(ticket.status)}`}>
                          {ticket.status.replace('_', ' ')}
                        </span>
                      </div>
                      
                      <p className="text-gray-600 mb-3">{ticket.message}</p>
                      
                      <div className="flex items-center space-x-4 text-sm text-gray-500">
                        <span className="flex items-center">
                          <FiTag className="mr-1 h-4 w-4" />
                          {ticket.category}
                        </span>
                        <span className="flex items-center">
                          <FiClock className="mr-1 h-4 w-4" />
                          {new Date(ticket.created_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                      <span className="text-sm text-gray-500">#{ticket.ticket_number || ticket.id}</span>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-12">
                <FiMessageSquare className="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No tickets yet</h3>
                <p className="text-gray-600 mb-4">
                  You haven't created any support tickets yet. Need help? Create your first ticket.
                </p>
                <button
                  onClick={() => setShowTicketModal(true)}
                  className="bg-primary hover:bg-accent-1 text-white px-6 py-3 rounded-lg transition-colors"
                >
                  Create Your First Ticket
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Ticket Modal */}
      {showTicketModal && (
        <TicketModal
          isOpen={showTicketModal}
          onClose={() => setShowTicketModal(false)}
          onSuccess={(newTicket) => {
            loadData(); // Reload tickets
            setShowTicketModal(false);
            setActiveTab('tickets');
          }}
        />
      )}

      {/* Article Modal */}
      {showArticleModal && selectedArticle && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-gray-200">
              <div className="flex items-center space-x-3">
                <FiBookOpen className="h-6 w-6 text-primary" />
                <div>
                  <h2 className="text-xl font-semibold text-gray-900">{selectedArticle.title}</h2>
                  <p className="text-sm text-gray-500">{selectedArticle.category_name}</p>
                </div>
              </div>
              <button
                onClick={() => setShowArticleModal(false)}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <FiX className="h-6 w-6" />
              </button>
            </div>
            
            <div className="p-6">
              <div 
                className="prose prose-lg max-w-none"
                dangerouslySetInnerHTML={{ __html: selectedArticle.content.replace(/\n/g, '<br/>') }}
              />
              
              <div className="mt-8 pt-6 border-t border-gray-200">
                <div className="flex items-center justify-between text-sm text-gray-500">
                  <div className="flex items-center space-x-4">
                    <span className="flex items-center">
                      <FiEye className="mr-1 h-4 w-4" />
                      {selectedArticle.view_count} views
                    </span>
                    <span className="flex items-center">
                      <FiThumbsUp className="mr-1 h-4 w-4" />
                      {selectedArticle.helpful_count} helpful
                    </span>
                  </div>
                  <span>
                    Updated {new Date(selectedArticle.updated_at).toLocaleDateString()}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SupportPage;
