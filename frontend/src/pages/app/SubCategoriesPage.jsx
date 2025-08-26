import React, { useState, useEffect } from 'react';
import { 
  FiPlus, FiEdit, FiTrash, FiSearch, FiTag, FiAlertCircle, 
  FiCheck, FiX, FiEye, FiPackage, FiTrendingUp, FiImage, FiUpload, FiCamera,
  FiFolder, FiFolderPlus
} from 'react-icons/fi';
import { subCategoriesAPI, categoriesAPI } from '../../services/api';

const SubCategoriesPage = () => {
  const [subCategories, setSubCategories] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [showModal, setShowModal] = useState(false);
  const [editingSubCategory, setEditingSubCategory] = useState(null);
  const [imageFile, setImageFile] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [formData, setFormData] = useState({
    category_id: '',
    name: '',
    description: '',
    color: '#e41e5b',
    image_url: '',
    sort_order: 0,
    status: 'active'
  });

  const predefinedColors = [
    '#e41e5b', '#9a0864', '#2c2c2c', '#746354', '#a67c00',
    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
    '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
  ];

  const fetchSubCategories = async () => {
    try {
      setLoading(true);
      setError(null);
      const params = {};
      if (selectedCategory !== 'all') {
        params.category_id = selectedCategory;
      }
      const response = await subCategoriesAPI.getAll(params);
      
      if (response.data.success) {
        setSubCategories(response.data.data);
      } else {
        setError('Failed to load sub-categories');
      }
    } catch (err) {
      setError('Error loading sub-categories: ' + err.message);
      console.error('Sub-categories error:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await categoriesAPI.getAll();
      if (response.data.success) {
        setCategories(response.data.data);
      }
    } catch (err) {
      console.error('Categories error:', err);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  useEffect(() => {
    fetchSubCategories();
  }, [selectedCategory]);

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setImageFile(file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setImagePreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.name.trim() || !formData.category_id) {
      setError('Sub-category name and parent category are required');
      return;
    }

    try {
      setError(null);
      
      // Create FormData for image upload
      const submitData = new FormData();
      Object.keys(formData).forEach(key => {
        submitData.append(key, formData[key]);
      });
      
      if (imageFile) {
        submitData.append('image', imageFile);
      }

      let response;
      if (editingSubCategory) {
        // For update, include the ID in the FormData
        submitData.append('id', editingSubCategory.id);
        response = await subCategoriesAPI.update(submitData);
      } else {
        response = await subCategoriesAPI.create(submitData);
      }
      
      if (response.data.success) {
        setShowModal(false);
        setEditingSubCategory(null);
        setFormData({ 
          category_id: '', 
          name: '', 
          description: '', 
          color: '#e41e5b', 
          image_url: '',
          sort_order: 0,
          status: 'active'
        });
        setImageFile(null);
        setImagePreview(null);
        fetchSubCategories();
      } else {
        setError(response.data.error || 'Failed to save sub-category');
      }
    } catch (err) {
      setError('Error saving sub-category: ' + err.message);
      console.error('Save error:', err);
    }
  };

  const handleEdit = (subCategory) => {
    setEditingSubCategory(subCategory);
    setFormData({
      category_id: subCategory.category_id,
      name: subCategory.name,
      description: subCategory.description || '',
      color: subCategory.color || '#e41e5b',
      image_url: subCategory.image_url || '',
      sort_order: subCategory.sort_order || 0,
      status: subCategory.status || 'active'
    });
    setImagePreview(subCategory.image_url || '');
    setShowModal(true);
  };

  const handleDelete = async (subCategoryId) => {
    if (!window.confirm('Are you sure you want to delete this sub-category? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await subCategoriesAPI.delete(subCategoryId);
      
      if (response.data.success) {
        fetchSubCategories();
      } else {
        setError(response.data.error || 'Failed to delete sub-category');
      }
    } catch (err) {
      setError('Error deleting sub-category: ' + err.message);
      console.error('Delete error:', err);
    }
  };

  const handleNewSubCategory = () => {
    setEditingSubCategory(null);
    setFormData({ 
      category_id: selectedCategory !== 'all' ? selectedCategory : '', 
      name: '', 
      description: '', 
      color: '#e41e5b', 
      image_url: '',
      sort_order: 0,
      status: 'active'
    });
    setImagePreview('');
    setShowModal(true);
  };

  const filteredSubCategories = subCategories.filter(subCategory =>
    subCategory.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (subCategory.description && subCategory.description.toLowerCase().includes(searchTerm.toLowerCase())) ||
    (subCategory.category_name && subCategory.category_name.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading Sub-Categories...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Sub-Categories</h1>
            <p className="text-[#746354] mt-1">
              Manage detailed sub-categories for better product organization
            </p>
          </div>
          <button
            onClick={handleNewSubCategory}
            className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
          >
            <FiPlus className="h-4 w-4 mr-2" />
            Add Sub-Category
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

      {/* Search and Filters */}
      <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#746354]" />
            <input
              type="text"
              placeholder="Search sub-categories..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
            />
          </div>
          <select
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
            className="px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
          >
            <option value="all">All Categories</option>
            {categories.map(category => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Sub-Categories Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {filteredSubCategories.map((subCategory) => (
          <div
            key={subCategory.id}
            className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
          >
            {/* Sub-Category Image */}
            <div className="h-32 bg-gray-200 relative">
              {subCategory.image_url ? (
                <img 
                  src={subCategory.image_url} 
                  alt={subCategory.name}
                  className="w-full h-full object-cover"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.nextSibling.style.display = 'flex';
                  }}
                />
              ) : null}
              <div className={`w-full h-full flex items-center justify-center ${subCategory.image_url ? 'hidden' : 'flex'}`}>
                <div className="text-center">
                  <FiFolder className="h-8 w-8 text-gray-400 mx-auto mb-1" />
                  <p className="text-xs text-gray-500">No Image</p>
                </div>
              </div>
              
              {/* Action Buttons */}
              <div className="absolute top-2 right-2 flex items-center space-x-1">
                <button
                  onClick={() => handleEdit(subCategory)}
                  className="p-1.5 bg-white/80 backdrop-blur-sm text-[#746354] hover:text-[#e41e5b] hover:bg-white rounded transition-colors"
                  title="Edit Sub-Category"
                >
                  <FiEdit className="h-3 w-3" />
                </button>
                <button
                  onClick={() => handleDelete(subCategory.id)}
                  className="p-1.5 bg-white/80 backdrop-blur-sm text-[#746354] hover:text-red-600 hover:bg-white rounded transition-colors"
                  title="Delete Sub-Category"
                >
                  <FiTrash className="h-3 w-3" />
                </button>
              </div>
              
              {/* Color Indicator */}
              <div className="absolute bottom-2 left-2">
                <div 
                  className="w-6 h-6 rounded-full border-2 border-white shadow-sm"
                  style={{ backgroundColor: subCategory.color }}
                ></div>
              </div>

              {/* Status Badge */}
              <div className="absolute top-2 left-2">
                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                  subCategory.status === 'active' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-800'
                }`}>
                  {subCategory.status}
                </span>
              </div>
            </div>

            <div className="p-4">
              <div className="flex items-start justify-between mb-3">
                <div 
                  className="w-8 h-8 rounded-lg flex items-center justify-center"
                  style={{ backgroundColor: subCategory.color + '20' }}
                >
                  <FiFolder 
                    className="h-4 w-4" 
                    style={{ color: subCategory.color }}
                  />
                </div>
              </div>

              <div>
                <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">
                  {subCategory.name}
                </h3>
                {subCategory.description && (
                  <p className="text-[#746354] text-sm mb-3 line-clamp-2">
                    {subCategory.description}
                  </p>
                )}
                
                {/* Parent Category */}
                {subCategory.category_name && (
                  <div className="text-xs text-[#746354] mb-2">
                    <span className="font-medium">Parent:</span> {subCategory.category_name}
                  </div>
                )}
                
                <div className="flex items-center justify-between text-xs text-[#746354]">
                  <span>
                    {subCategory.product_count || 0} products
                  </span>
                  <span>
                    {new Date(subCategory.created_at).toLocaleDateString()}
                  </span>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Empty State */}
      {filteredSubCategories.length === 0 && (
        <div className="text-center py-12">
          <FiFolder className="h-12 w-12 text-[#746354] mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">
            {searchTerm || selectedCategory !== 'all' ? 'No sub-categories found' : 'No sub-categories yet'}
          </h3>
          <p className="text-[#746354] mb-4">
            {searchTerm || selectedCategory !== 'all'
              ? 'Try adjusting your search terms or category filter'
              : 'Create your first sub-category to organize products in detail'
            }
          </p>
          {!searchTerm && selectedCategory === 'all' && (
            <button
              onClick={handleNewSubCategory}
              className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors mx-auto"
            >
              <FiPlus className="h-4 w-4 mr-2" />
              Create First Sub-Category
            </button>
          )}
        </div>
      )}

      {/* Sub-Category Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-[#2c2c2c]">
                {editingSubCategory ? 'Edit Sub-Category' : 'New Sub-Category'}
              </h2>
              <button
                onClick={() => setShowModal(false)}
                className="text-[#746354] hover:text-[#2c2c2c]"
              >
                <FiX className="h-6 w-6" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Parent Category */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Parent Category *
                </label>
                <select
                  value={formData.category_id}
                  onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  required
                >
                  <option value="">Select Parent Category</option>
                  {categories.map(category => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Sub-Category Name */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Sub-Category Name *
                </label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  placeholder="e.g., Android Phones, Gaming Laptops"
                  required
                />
              </div>

              {/* Description */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Description
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  placeholder="Optional description for this sub-category"
                />
              </div>

              {/* Image Upload */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Sub-Category Image
                </label>
                <div className="flex items-center space-x-3">
                  <label htmlFor="sub-image-upload" className="flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg cursor-pointer hover:bg-gray-300 transition-colors">
                    <FiCamera className="h-5 w-5 mr-2" />
                    Choose Image
                    <input
                      type="file"
                      id="sub-image-upload"
                      accept="image/*"
                      onChange={handleImageChange}
                      className="hidden"
                    />
                  </label>
                  {imagePreview && (
                    <div className="flex-1 flex items-center">
                      <img src={imagePreview} alt="Preview" className="h-12 w-12 object-cover rounded-md mr-2" />
                      <span className="text-sm text-[#746354]">{imageFile ? imageFile.name : 'No image selected'}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Sort Order */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Sort Order
                </label>
                <input
                  type="number"
                  value={formData.sort_order}
                  onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  placeholder="0"
                  min="0"
                />
                <p className="text-xs text-[#746354] mt-1">
                  Lower numbers appear first in the list
                </p>
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
                </select>
              </div>

              {/* Color Selection */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-3">
                  Sub-Category Color
                </label>
                <div className="grid grid-cols-5 gap-2">
                  {predefinedColors.map((color) => (
                    <button
                      key={color}
                      type="button"
                      onClick={() => setFormData({ ...formData, color })}
                      className={`w-10 h-10 rounded-lg border-2 transition-all ${
                        formData.color === color
                          ? 'border-[#2c2c2c] scale-110'
                          : 'border-gray-300 hover:border-[#e41e5b]'
                      }`}
                      style={{ backgroundColor: color }}
                      title={color}
                    >
                      {formData.color === color && (
                        <FiCheck className="h-5 w-5 text-white mx-auto" />
                      )}
                    </button>
                  ))}
                </div>
                <div className="mt-2 flex items-center space-x-2">
                  <div 
                    className="w-4 h-4 rounded-full border border-gray-300"
                    style={{ backgroundColor: formData.color }}
                  ></div>
                  <span className="text-sm text-[#746354]">{formData.color}</span>
                </div>
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
                  {editingSubCategory ? 'Update Sub-Category' : 'Create Sub-Category'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default SubCategoriesPage;
