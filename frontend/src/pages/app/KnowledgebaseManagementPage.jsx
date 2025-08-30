import React, { useState, useEffect } from 'react';
import {
  FiPlus, FiEdit, FiTrash, FiSearch, FiFilter, FiEye, FiFileText,
  FiFolder, FiTag, FiCalendar, FiUser, FiCheckCircle, FiXCircle,
  FiMoreVertical, FiDownload, FiRotateCw, FiAlertCircle, FiGrid,
  FiList, FiBookOpen, FiSettings, FiArrowRight, FiArrowLeft
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';

const KnowledgebaseManagementPage = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('categories');
  const [categories, setCategories] = useState([]);
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showArticleModal, setShowArticleModal] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [editingArticle, setEditingArticle] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [viewMode, setViewMode] = useState('grid');
  const [pagination, setPagination] = useState({
    page: 1,
    limit: 20,
    total: 0,
    pages: 0
  });

  const [categoryForm, setCategoryForm] = useState({
    name: '',
    slug: '',
    description: '',
    icon: 'help-circle',
    sort_order: 1
  });

  const [articleForm, setArticleForm] = useState({
    title: '',
    slug: '',
    content: '',
    excerpt: '',
    category_id: '',
    tags: '',
    published: true,
    featured: false
  });

  // Fetch categories
  const fetchCategories = async () => {
    try {
      setLoading(true);
      const response = await fetch('/knowledgebase-management.php/categories', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setCategories(data.data.categories);
        setPagination(data.data.pagination);
      } else {
        setError('Failed to load categories');
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
      setError('Failed to load categories');
    } finally {
      setLoading(false);
    }
  };

  // Fetch articles
  const fetchArticles = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: pagination.page,
        limit: pagination.limit,
        ...(selectedCategory && { category_id: selectedCategory }),
        ...(searchTerm && { search: searchTerm })
      });

      const response = await fetch(`/knowledgebase-management.php/articles?${params}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setArticles(data.data.articles);
        setPagination(data.data.pagination);
      } else {
        setError('Failed to load articles');
      }
    } catch (error) {
      console.error('Error fetching articles:', error);
      setError('Failed to load articles');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (activeTab === 'categories') {
      fetchCategories();
    } else {
      fetchArticles();
    }
  }, [activeTab, selectedCategory, searchTerm, pagination.page]);

  // Category management functions
  const handleCategorySubmit = async (e) => {
    e.preventDefault();
    try {
      const method = editingCategory ? 'PUT' : 'POST';
      const response = await fetch('/knowledgebase-management.php/categories', {
        method,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(editingCategory ? { ...categoryForm, id: editingCategory.id } : categoryForm)
      });

      const data = await response.json();
      
      if (data.success) {
        setSuccess(editingCategory ? 'Category updated successfully' : 'Category created successfully');
        setShowCategoryModal(false);
        setEditingCategory(null);
        setCategoryForm({ name: '', slug: '', description: '', icon: 'help-circle', sort_order: 1 });
        fetchCategories();
      } else {
        setError(data.error || 'Failed to save category');
      }
    } catch (error) {
      console.error('Error saving category:', error);
      setError('Failed to save category');
    }
  };

  const handleCategoryDelete = async (categoryId) => {
    if (!window.confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(`/knowledgebase-management.php/categories?id=${categoryId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      
      if (data.success) {
        setSuccess('Category deleted successfully');
        fetchCategories();
      } else {
        setError(data.error || 'Failed to delete category');
      }
    } catch (error) {
      console.error('Error deleting category:', error);
      setError('Failed to delete category');
    }
  };

  // Article management functions
  const handleArticleSubmit = async (e) => {
    e.preventDefault();
    try {
      const method = editingArticle ? 'PUT' : 'POST';
      const response = await fetch('/knowledgebase-management.php/articles', {
        method,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(editingArticle ? { ...articleForm, id: editingArticle.id } : articleForm)
      });

      const data = await response.json();
      
      if (data.success) {
        setSuccess(editingArticle ? 'Article updated successfully' : 'Article created successfully');
        setShowArticleModal(false);
        setEditingArticle(null);
        setArticleForm({
          title: '', slug: '', content: '', excerpt: '', category_id: '', tags: '', published: true, featured: false
        });
        fetchArticles();
      } else {
        setError(data.error || 'Failed to save article');
      }
    } catch (error) {
      console.error('Error saving article:', error);
      setError('Failed to save article');
    }
  };

  const handleArticleDelete = async (articleId) => {
    if (!window.confirm('Are you sure you want to delete this article? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(`/knowledgebase-management.php/articles?id=${articleId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      
      if (data.success) {
        setSuccess('Article deleted successfully');
        fetchArticles();
      } else {
        setError(data.error || 'Failed to delete article');
      }
    } catch (error) {
      console.error('Error deleting article:', error);
      setError('Failed to delete article');
    }
  };

  const openCategoryModal = (category = null) => {
    if (category) {
      setEditingCategory(category);
      setCategoryForm({
        name: category.name,
        slug: category.slug,
        description: category.description,
        icon: category.icon || 'help-circle',
        sort_order: category.sort_order || 1
      });
    } else {
      setEditingCategory(null);
      setCategoryForm({ name: '', slug: '', description: '', icon: 'help-circle', sort_order: 1 });
    }
    setShowCategoryModal(true);
  };

  const openArticleModal = (article = null) => {
    if (article) {
      setEditingArticle(article);
      setArticleForm({
        title: article.title,
        slug: article.slug,
        content: article.content,
        excerpt: article.excerpt || '',
        category_id: article.category_id,
        tags: article.tags || '',
        published: article.published,
        featured: article.featured || false
      });
    } else {
      setEditingArticle(null);
      setArticleForm({
        title: '', slug: '', content: '', excerpt: '', category_id: '', tags: '', published: true, featured: false
      });
    }
    setShowArticleModal(true);
  };

  const clearMessages = () => {
    setError(null);
    setSuccess(null);
  };

  useEffect(() => {
    const timer = setTimeout(clearMessages, 5000);
    return () => clearTimeout(timer);
  }, [error, success]);

  if (loading && categories.length === 0 && articles.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <FiRotateCw className="animate-spin h-8 w-8 text-[#e41e5b] mx-auto mb-4" />
          <p className="text-gray-600">Loading knowledgebase management...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Knowledgebase Management</h1>
              <p className="mt-2 text-gray-600">Manage categories and articles for the support portal</p>
            </div>
            <div className="flex items-center space-x-4">
              <button
                onClick={() => setViewMode(viewMode === 'grid' ? 'list' : 'grid')}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                {viewMode === 'grid' ? <FiList className="h-5 w-5" /> : <FiGrid className="h-5 w-5" />}
              </button>
              <button
                onClick={() => setActiveTab(activeTab === 'categories' ? 'articles' : 'categories')}
                className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
              >
                {activeTab === 'categories' ? (
                  <>
                    <FiFileText className="h-4 w-4 mr-2" />
                    Switch to Articles
                  </>
                ) : (
                  <>
                    <FiFolder className="h-4 w-4 mr-2" />
                    Switch to Categories
                  </>
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Messages */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div className="flex items-center">
              <FiAlertCircle className="h-5 w-5 text-red-400 mr-2" />
              <span className="text-red-800">{error}</span>
            </div>
          </div>
        )}

        {success && (
          <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div className="flex items-center">
              <FiCheckCircle className="h-5 w-5 text-green-400 mr-2" />
              <span className="text-green-800">{success}</span>
            </div>
          </div>
        )}

        {/* Tabs */}
        <div className="mb-6">
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8">
              <button
                onClick={() => setActiveTab('categories')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'categories'
                    ? 'border-[#e41e5b] text-[#e41e5b]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <FiFolder className="inline h-4 w-4 mr-2" />
                Categories ({categories.length})
              </button>
              <button
                onClick={() => setActiveTab('articles')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'articles'
                    ? 'border-[#e41e5b] text-[#e41e5b]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <FiFileText className="inline h-4 w-4 mr-2" />
                Articles ({articles.length})
              </button>
            </nav>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="mb-6 flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <div className="relative">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <input
                type="text"
                placeholder={`Search ${activeTab}...`}
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e41e5b] focus:border-transparent"
              />
            </div>
            {activeTab === 'articles' && (
              <select
                value={selectedCategory || ''}
                onChange={(e) => setSelectedCategory(e.target.value || null)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e41e5b] focus:border-transparent"
              >
                <option value="">All Categories</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            )}
          </div>
          <button
            onClick={() => activeTab === 'categories' ? openCategoryModal() : openArticleModal()}
            className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
          >
            <FiPlus className="h-4 w-4 mr-2" />
            Add {activeTab === 'categories' ? 'Category' : 'Article'}
          </button>
        </div>

        {/* Content */}
        {activeTab === 'categories' ? (
          <CategoriesTab
            categories={categories}
            viewMode={viewMode}
            onEdit={openCategoryModal}
            onDelete={handleCategoryDelete}
            loading={loading}
          />
        ) : (
          <ArticlesTab
            articles={articles}
            viewMode={viewMode}
            onEdit={openArticleModal}
            onDelete={handleArticleDelete}
            loading={loading}
            pagination={pagination}
            onPageChange={(page) => setPagination(prev => ({ ...prev, page }))}
          />
        )}
      </div>

      {/* Category Modal */}
      {showCategoryModal && (
        <CategoryModal
          form={categoryForm}
          setForm={setCategoryForm}
          onSubmit={handleCategorySubmit}
          onClose={() => setShowCategoryModal(false)}
          editing={editingCategory}
        />
      )}

      {/* Article Modal */}
      {showArticleModal && (
        <ArticleModal
          form={articleForm}
          setForm={setArticleForm}
          onSubmit={handleArticleSubmit}
          onClose={() => setShowArticleModal(false)}
          editing={editingArticle}
          categories={categories}
        />
      )}
    </div>
  );
};

export default KnowledgebaseManagementPage;
