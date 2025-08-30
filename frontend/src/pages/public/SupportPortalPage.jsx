import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { supportAPI } from '../../services/api';
import {
  FiSearch,
  FiBookOpen,
  FiMessageSquare,
  FiPlus,
  FiFilter,
  FiGrid,
  FiList,
  FiEye,
  FiThumbsUp,
  FiThumbsDown,
  FiClock,
  FiUser,
  FiTag,
  FiArrowRight,
  FiHelpCircle,
  FiFileText,
  FiSettings,
  FiTruck,
  FiShield,
  FiTool,
  FiCreditCard,
  FiShoppingCart,
  FiUsers,
  FiBarChart2,
  FiMonitor,
  FiX
} from 'react-icons/fi';
import TicketModal from '../../components/support/TicketModal';
import KnowledgebaseArticle from '../../components/support/KnowledgebaseArticle';

const SupportPortalPage = () => {
  const { user } = useAuth();
  const [articles, setArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [viewMode, setViewMode] = useState('grid');
  const [showTicketModal, setShowTicketModal] = useState(false);
  const [selectedArticle, setSelectedArticle] = useState(null);
  const [showArticleModal, setShowArticleModal] = useState(false);
  const [filteredArticles, setFilteredArticles] = useState([]);
  const [activeTab, setActiveTab] = useState('knowledgebase');

  // Category icons mapping
  const categoryIcons = {
    1: FiHelpCircle, // Getting Started
    2: FiShoppingCart, // Sales & Transactions
    3: FiTruck, // Inventory Management
    4: FiUsers, // Customer Management
    5: FiBarChart2, // Reports & Analytics
    6: FiMonitor, // Hardware & Setup
    7: FiSettings, // Integrations
    8: FiShield, // Security & Permissions
    9: FiTool // Troubleshooting
  };

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    filterArticles();
  }, [articles, searchTerm, selectedCategory]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [articlesResponse, categoriesResponse] = await Promise.all([
        supportAPI.getKnowledgebase(),
        supportAPI.getCategories()
      ]);

      // Handle the data structure correctly
      const articlesData = articlesResponse.data?.articles || [];
      const categoriesData = categoriesResponse.data || [];

      setArticles(articlesData);
      setCategories(categoriesData);
      setFilteredArticles(articlesData);

      if (user) {
        try {
          const ticketsResponse = await supportAPI.getTickets();
          const ticketsData = ticketsResponse.data?.data?.tickets || ticketsResponse.data?.tickets || [];
          setTickets(ticketsData);
        } catch (ticketError) {
          console.error('Error loading tickets:', ticketError);
          setTickets([]);
        }
      }
    } catch (error) {
      console.error('Error loading support data:', error);
      // Set default empty arrays to prevent map errors
      setArticles([]);
      setCategories([]);
      setFilteredArticles([]);
    } finally {
      setLoading(false);
    }
  };

  const filterArticles = () => {
    let filtered = articles;

    // Filter by search term
    if (searchTerm) {
      filtered = filtered.filter(article =>
        article.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        article.content.toLowerCase().includes(searchTerm.toLowerCase()) ||
        article.tags.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Filter by category
    if (selectedCategory !== 'all') {
      filtered = filtered.filter(article => article.category_id === parseInt(selectedCategory));
    }

    setFilteredArticles(filtered);
  };

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };

  const handleCategoryChange = (categoryId) => {
    setSelectedCategory(categoryId);
  };

  const handleViewModeChange = (mode) => {
    setViewMode(mode);
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

  const getCategoryIcon = (categoryId) => {
    const IconComponent = categoryIcons[categoryId] || FiFileText;
    return <IconComponent className="h-5 w-5" />;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="flex items-center justify-center min-h-screen">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-primary"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-br from-primary via-primary to-accent-1 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
          <div className="text-center">
            <h1 className="text-4xl md:text-5xl font-bold mb-6">
              How can we help you?
            </h1>
            <p className="text-xl md:text-2xl mb-8 text-white/90">
              Find answers, get support, and make the most of your Ardent POS system
            </p>
            
                         {/* Search Bar */}
             <div className="max-w-2xl mx-auto">
               <div className="relative">
                 <FiSearch className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
                 <input
                   type="text"
                   placeholder="Search for help articles, guides, and solutions..."
                   value={searchTerm}
                   onChange={handleSearch}
                   className="w-full pl-12 pr-4 py-4 text-lg text-gray-900 bg-white/95 backdrop-blur-sm rounded-xl shadow-xl focus:outline-none focus:ring-2 focus:ring-white/30 focus:bg-white transition-all duration-200"
                 />
               </div>
             </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Quick Actions */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
            <div className="flex items-center mb-4">
              <FiBookOpen className="h-8 w-8 text-primary mr-3" />
              <h3 className="text-xl font-semibold text-gray-900">Knowledge Base</h3>
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
              <h3 className="text-xl font-semibold text-gray-900">Support Tickets</h3>
            </div>
            <p className="text-gray-600 mb-4">
              Create a ticket for personalized support from our team
            </p>
            <button
              onClick={() => setShowTicketModal(true)}
              className="text-primary hover:text-accent-1 font-medium flex items-center"
            >
              Create Ticket <FiArrowRight className="ml-2 h-4 w-4" />
            </button>
          </div>

          <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl hover:scale-105 transition-all duration-300">
            <div className="flex items-center mb-4">
              <FiHelpCircle className="h-8 w-8 text-primary mr-3" />
              <h3 className="text-xl font-semibold text-gray-900">Live Chat</h3>
            </div>
            <p className="text-gray-600 mb-4">
              Get instant help from our AI assistant or human support team
            </p>
            <button
              onClick={() => {
                // Trigger chat widget
                const chatButton = document.querySelector('[data-chat-trigger]');
                if (chatButton) chatButton.click();
              }}
              className="text-primary hover:text-accent-1 font-medium flex items-center"
            >
              Start Chat <FiArrowRight className="ml-2 h-4 w-4" />
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
              {user && (
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
              )}
            </nav>
          </div>
        </div>

        {/* Knowledge Base Tab */}
        {activeTab === 'knowledgebase' && (
          <div>
            {/* Filters and Controls */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
              {/* Category Filter */}
              <div className="flex flex-wrap gap-2">
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
                    className={`px-4 py-2 rounded-full text-sm font-medium transition-colors flex items-center ${
                      selectedCategory === category.id.toString()
                        ? 'bg-primary text-white'
                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    }`}
                  >
                    {getCategoryIcon(category.id)}
                    <span className="ml-2">{category.name}</span>
                  </button>
                ))}
              </div>

              {/* View Mode Toggle */}
              <div className="flex items-center space-x-2">
                <span className="text-sm text-gray-600">View:</span>
                <button
                  onClick={() => handleViewModeChange('grid')}
                  className={`p-2 rounded transition-colors ${
                    viewMode === 'grid'
                      ? 'bg-primary text-white'
                      : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                  }`}
                >
                  <FiGrid className="h-4 w-4" />
                </button>
                <button
                  onClick={() => handleViewModeChange('list')}
                  className={`p-2 rounded transition-colors ${
                    viewMode === 'list'
                      ? 'bg-primary text-white'
                      : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                  }`}
                >
                  <FiList className="h-4 w-4" />
                </button>
              </div>
            </div>

            {/* Search Results Info */}
            {searchTerm && (
              <div className="mb-6 p-4 bg-blue-50 rounded-lg">
                <p className="text-blue-800">
                  Found {filteredArticles.length} article{filteredArticles.length !== 1 ? 's' : ''} for "{searchTerm}"
                </p>
              </div>
            )}

            {/* Articles Grid/List */}
            {filteredArticles.length > 0 ? (
              <div className={viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' : 'space-y-4'}>
                {filteredArticles.map((article) => (
                  <KnowledgebaseArticle
                    key={article.id}
                    article={article}
                    viewMode={viewMode}
                    categoryName={getCategoryName(article.category_id)}
                    categoryIcon={getCategoryIcon(article.category_id)}
                    onClick={() => handleArticleClick(article.id)}
                  />
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <FiSearch className="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No articles found</h3>
                <p className="text-gray-600">
                  {searchTerm
                    ? `No articles match your search for "${searchTerm}". Try different keywords or browse all categories.`
                    : 'No articles available in this category.'}
                </p>
                {searchTerm && (
                  <button
                    onClick={() => setSearchTerm('')}
                    className="mt-4 text-primary hover:text-accent-1 font-medium"
                  >
                    Clear search
                  </button>
                )}
              </div>
            )}
          </div>
        )}

        {/* My Tickets Tab */}
        {activeTab === 'tickets' && user && (
          <div>
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-gray-900">My Support Tickets</h2>
              <button
                onClick={() => setShowTicketModal(true)}
                className="bg-primary hover:bg-accent-1 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
              >
                <FiPlus className="h-4 w-4 mr-2" />
                New Ticket
              </button>
            </div>

            {tickets.length > 0 ? (
              <div className="space-y-4">
                {tickets.map((ticket) => (
                  <div key={ticket.id} className="bg-white rounded-lg shadow-md p-6">
                    <div className="flex justify-between items-start mb-4">
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{ticket.subject}</h3>
                        <p className="text-gray-600 text-sm">#{ticket.ticket_number}</p>
                      </div>
                      <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                        ticket.status === 'open' ? 'bg-green-100 text-green-800' :
                        ticket.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        {ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}
                      </span>
                    </div>
                    <p className="text-gray-700 mb-4">{ticket.message}</p>
                    <div className="flex items-center justify-between text-sm text-gray-500">
                      <div className="flex items-center">
                        <FiClock className="h-4 w-4 mr-1" />
                        Created {new Date(ticket.created_at).toLocaleDateString()}
                      </div>
                      <div className="flex items-center">
                        <FiTag className="h-4 w-4 mr-1" />
                        {ticket.category}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
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
        )}
      </div>

      {/* Ticket Modal */}
      {showTicketModal && (
        <TicketModal
          isOpen={showTicketModal}
          onClose={() => setShowTicketModal(false)}
          onSuccess={(newTicket) => {
            setTickets([newTicket, ...tickets]);
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
                {getCategoryIcon(selectedArticle.category_id)}
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

export default SupportPortalPage;
