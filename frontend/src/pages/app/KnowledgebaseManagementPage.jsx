import React, { useState, useEffect } from 'react';
import { FiPlus, FiEdit, FiTrash2, FiSearch, FiFilter, FiEye, FiEyeOff, FiBookOpen, FiTag } from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const KnowledgebaseManagementPage = () => {
  const [activeTab, setActiveTab] = useState('categories');
  const [categories, setCategories] = useState([]);
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [showModal, setShowModal] = useState(false);
  const [modalType, setModalType] = useState('create');
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    slug: '',
    category_id: '',
    title: '',
    content: '',
    published: true,
    meta_description: '',
    tags: ''
  });

  // Load data on component mount
  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    setError('');
    try {
      if (activeTab === 'categories') {
        const response = await superAdminAPI.getKnowledgebaseCategories();
        setCategories(response.data?.data?.categories || []);
      } else {
        const response = await superAdminAPI.getKnowledgebaseArticles();
        setArticles(response.data?.data?.articles || []);
      }
    } catch (err) {
      setError('Failed to load data. Please try again.');
      console.error('Error loading data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleTabChange = (tab) => {
    setActiveTab(tab);
    setSearchTerm('');
    setSelectedCategory('all');
    loadData();
  };

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };

  const handleFilter = (e) => {
    setSelectedCategory(e.target.value);
  };

  const filteredData = () => {
    let data = activeTab === 'categories' ? categories : articles;
    
    if (searchTerm) {
      data = data.filter(item => 
        activeTab === 'categories' 
          ? item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.description.toLowerCase().includes(searchTerm.toLowerCase())
          : item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.content.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    if (activeTab === 'articles' && selectedCategory !== 'all') {
      data = data.filter(item => item.category_id === parseInt(selectedCategory));
    }

    return data;
  };

  const openModal = (type, item = null) => {
    setModalType(type);
    setEditingItem(item);
    if (item) {
      setFormData({
        name: item.name || '',
        description: item.description || '',
        slug: item.slug || '',
        category_id: item.category_id || '',
        title: item.title || '',
        content: item.content || '',
        published: item.published !== undefined ? item.published : true,
        meta_description: item.meta_description || '',
        tags: item.tags || ''
      });
    } else {
      setFormData({
        name: '',
        description: '',
        slug: '',
        category_id: '',
        title: '',
        content: '',
        published: true,
        meta_description: '',
        tags: ''
      });
    }
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setEditingItem(null);
    setFormData({
      name: '',
      description: '',
      slug: '',
      category_id: '',
      title: '',
      content: '',
      published: true,
      meta_description: '',
      tags: ''
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      if (activeTab === 'categories') {
        if (modalType === 'create') {
          await superAdminAPI.createKnowledgebaseCategory(formData);
        } else {
          await superAdminAPI.updateKnowledgebaseCategory({ ...formData, id: editingItem.id });
        }
      } else {
        if (modalType === 'create') {
          await superAdminAPI.createKnowledgebaseArticle(formData);
        } else {
          await superAdminAPI.updateKnowledgebaseArticle({ ...formData, id: editingItem.id });
        }
      }
      
      closeModal();
      loadData();
    } catch (err) {
      setError('Failed to save. Please try again.');
      console.error('Error saving:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this item?')) return;

    setLoading(true);
    setError('');

    try {
      if (activeTab === 'categories') {
        await superAdminAPI.deleteKnowledgebaseCategory({ id });
      } else {
        await superAdminAPI.deleteKnowledgebaseArticle({ id });
      }
      loadData();
    } catch (err) {
      setError('Failed to delete. Please try again.');
      console.error('Error deleting:', err);
    } finally {
      setLoading(false);
    }
  };

  const togglePublished = async (item) => {
    try {
      if (activeTab === 'articles') {
        await superAdminAPI.updateKnowledgebaseArticle({
          id: item.id,
          published: !item.published
        });
        loadData();
      }
    } catch (err) {
      setError('Failed to update status. Please try again.');
      console.error('Error updating status:', err);
    }
  };

  const generateSlug = (text) => {
    return text
      .toLowerCase()
      .replace(/[^a-z0-9 -]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim('-');
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));

    // Auto-generate slug for categories
    if (activeTab === 'categories' && name === 'name') {
      setFormData(prev => ({
        ...prev,
        slug: generateSlug(value)
      }));
    }
  };

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Knowledgebase Management</h1>
          <p className="text-gray-600">Manage knowledgebase categories and articles</p>
        </div>

        {/* Error Message */}
        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-red-800">{error}</p>
          </div>
        )}

        {/* Tabs */}
        <div className="mb-6">
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8">
              <button
                onClick={() => handleTabChange('categories')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'categories'
                    ? 'border-e41e5b text-e41e5b'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <FiTag className="inline mr-2" />
                Categories
              </button>
              <button
                onClick={() => handleTabChange('articles')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'articles'
                    ? 'border-e41e5b text-e41e5b'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <FiBookOpen className="inline mr-2" />
                Articles
              </button>
            </nav>
          </div>
        </div>

        {/* Search and Filter Bar */}
        <div className="mb-6 flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder={`Search ${activeTab}...`}
              value={searchTerm}
              onChange={handleSearch}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
            />
          </div>
          
          {activeTab === 'articles' && (
            <div className="relative">
              <FiFilter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <select
                value={selectedCategory}
                onChange={handleFilter}
                className="pl-10 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
              >
                <option value="all">All Categories</option>
                {categories.map(category => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            </div>
          )}

          <button
            onClick={() => openModal('create')}
            className="px-6 py-2 bg-e41e5b text-white rounded-lg hover:bg-9a0864 transition-colors flex items-center"
          >
            <FiPlus className="mr-2" />
            Add {activeTab === 'categories' ? 'Category' : 'Article'}
          </button>
        </div>

        {/* Content */}
        <div className="bg-white rounded-lg shadow">
          {loading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-e41e5b mx-auto"></div>
              <p className="mt-2 text-gray-600">Loading...</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              {activeTab === 'categories' ? (
                <CategoriesTable 
                  categories={filteredData()} 
                  onEdit={openModal}
                  onDelete={handleDelete}
                />
              ) : (
                <ArticlesTable 
                  articles={filteredData()}
                  categories={categories}
                  onEdit={openModal}
                  onDelete={handleDelete}
                  onTogglePublished={togglePublished}
                />
              )}
            </div>
          )}
        </div>

        {/* Modal */}
        {showModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
              <h2 className="text-xl font-semibold mb-4">
                {modalType === 'create' ? 'Create' : 'Edit'} {activeTab === 'categories' ? 'Category' : 'Article'}
              </h2>
              
              <form onSubmit={handleSubmit}>
                {activeTab === 'categories' ? (
                  <CategoryForm 
                    formData={formData}
                    onChange={handleInputChange}
                  />
                ) : (
                  <ArticleForm 
                    formData={formData}
                    categories={categories}
                    onChange={handleInputChange}
                  />
                )}
                
                <div className="flex justify-end space-x-3 mt-6">
                  <button
                    type="button"
                    onClick={closeModal}
                    className="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-4 py-2 bg-e41e5b text-white rounded-lg hover:bg-9a0864 disabled:opacity-50"
                  >
                    {loading ? 'Saving...' : 'Save'}
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

// Categories Table Component
const CategoriesTable = ({ categories, onEdit, onDelete }) => {
  return (
    <table className="min-w-full divide-y divide-gray-200">
      <thead className="bg-gray-50">
        <tr>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Articles</th>
          <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody className="bg-white divide-y divide-gray-200">
        {categories.map((category) => (
          <tr key={category.id} className="hover:bg-gray-50">
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm font-medium text-gray-900">{category.name}</div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm text-gray-500">{category.slug}</div>
            </td>
            <td className="px-6 py-4">
              <div className="text-sm text-gray-900 max-w-xs truncate">{category.description}</div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm text-gray-900">{category.article_count || 0}</div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                onClick={() => onEdit('edit', category)}
                className="text-e41e5b hover:text-9a0864 mr-3"
              >
                <FiEdit className="inline" />
              </button>
              <button
                onClick={() => onDelete(category.id)}
                className="text-red-600 hover:text-red-900"
              >
                <FiTrash2 className="inline" />
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

// Articles Table Component
const ArticlesTable = ({ articles, categories, onEdit, onDelete, onTogglePublished }) => {
  const getCategoryName = (categoryId) => {
    const category = categories.find(c => c.id === categoryId);
    return category ? category.name : 'Unknown';
  };

  return (
    <table className="min-w-full divide-y divide-gray-200">
      <thead className="bg-gray-50">
        <tr>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
          <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody className="bg-white divide-y divide-gray-200">
        {articles.map((article) => (
          <tr key={article.id} className="hover:bg-gray-50">
            <td className="px-6 py-4">
              <div className="text-sm font-medium text-gray-900 max-w-xs truncate">{article.title}</div>
              <div className="text-sm text-gray-500 max-w-xs truncate">{article.meta_description}</div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm text-gray-900">{getCategoryName(article.category_id)}</div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <button
                onClick={() => onTogglePublished(article)}
                className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  article.published
                    ? 'bg-green-100 text-green-800'
                    : 'bg-gray-100 text-gray-800'
                }`}
              >
                {article.published ? <FiEye className="mr-1" /> : <FiEyeOff className="mr-1" />}
                {article.published ? 'Published' : 'Draft'}
              </button>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm text-gray-900">{article.view_count || 0}</div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm text-gray-900">
                {new Date(article.created_at).toLocaleDateString()}
              </div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                onClick={() => onEdit('edit', article)}
                className="text-e41e5b hover:text-9a0864 mr-3"
              >
                <FiEdit className="inline" />
              </button>
              <button
                onClick={() => onDelete(article.id)}
                className="text-red-600 hover:text-red-900"
              >
                <FiTrash2 className="inline" />
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

// Category Form Component
const CategoryForm = ({ formData, onChange }) => {
  return (
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
        <input
          type="text"
          name="name"
          value={formData.name}
          onChange={onChange}
          required
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Slug</label>
        <input
          type="text"
          name="slug"
          value={formData.slug}
          onChange={onChange}
          required
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea
          name="description"
          value={formData.description}
          onChange={onChange}
          rows="3"
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
    </div>
  );
};

// Article Form Component
const ArticleForm = ({ formData, categories, onChange }) => {
  return (
    <div className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
        <input
          type="text"
          name="title"
          value={formData.title}
          onChange={onChange}
          required
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
        <select
          name="category_id"
          value={formData.category_id}
          onChange={onChange}
          required
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        >
          <option value="">Select a category</option>
          {categories.map(category => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Content</label>
        <textarea
          name="content"
          value={formData.content}
          onChange={onChange}
          required
          rows="8"
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
        <textarea
          name="meta_description"
          value={formData.meta_description}
          onChange={onChange}
          rows="2"
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Tags</label>
        <input
          type="text"
          name="tags"
          value={formData.tags}
          onChange={onChange}
          placeholder="Comma-separated tags"
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-e41e5b focus:border-transparent"
        />
      </div>
      
      <div className="flex items-center">
        <input
          type="checkbox"
          name="published"
          checked={formData.published}
          onChange={onChange}
          className="h-4 w-4 text-e41e5b focus:ring-e41e5b border-gray-300 rounded"
        />
        <label className="ml-2 block text-sm text-gray-900">Published</label>
      </div>
    </div>
  );
};

export default KnowledgebaseManagementPage;
