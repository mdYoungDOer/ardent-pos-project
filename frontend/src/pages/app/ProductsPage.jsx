import React, { useState, useEffect } from 'react';
import { FiPlus, FiEdit, FiTrash, FiSearch, FiPackage, FiAlertCircle } from 'react-icons/fi';
import { productsAPI } from '../../services/api';
import useAuthStore from '../../stores/authStore';

const ProductsPage = () => {
  const { user } = useAuthStore();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    price: '',
    stock: ''
  });

  const fetchProducts = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await productsAPI.getAll();
      if (response.data.success) {
        setProducts(response.data.data);
      } else {
        setError('Failed to load products');
      }
    } catch (err) {
      setError('Error loading products');
      console.error('Products error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingProduct) {
        await productsAPI.update({ ...formData, id: editingProduct.id });
      } else {
        await productsAPI.create(formData);
      }
      setShowAddModal(false);
      setEditingProduct(null);
      setFormData({ name: '', description: '', price: '', stock: '' });
      fetchProducts();
    } catch (err) {
      console.error('Product save error:', err);
    }
  };

  const handleDelete = async (productId) => {
    if (window.confirm('Are you sure you want to delete this product?')) {
      try {
        await productsAPI.delete(productId);
        fetchProducts();
      } catch (err) {
        console.error('Delete error:', err);
      }
    }
  };

  const handleEdit = (product) => {
    setEditingProduct(product);
    setFormData({
      name: product.name,
      description: product.description || '',
      price: product.price,
      stock: product.stock || 0
    });
    setShowAddModal(true);
  };

  const filteredProducts = products.filter(product =>
    product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (product.description && product.description.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading products...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Products</h1>
            <p className="text-[#746354] mt-1">
              Manage your product catalog and inventory
            </p>
          </div>
          <button
            onClick={() => setShowAddModal(true)}
            className="flex items-center px-6 py-3 bg-[#e41e5b] text-white rounded-xl hover:bg-[#9a0864] transition-colors shadow-sm"
          >
            <FiPlus className="h-5 w-5 mr-2" />
            Add Product
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6 mb-6">
        <div className="relative">
          <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-[#746354]" />
          <input
            type="text"
            placeholder="Search products by name or description..."
            className="w-full pl-10 pr-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Products Grid */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 overflow-hidden">
        {error ? (
          <div className="p-8 text-center">
            <FiAlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Products</h3>
            <p className="text-red-600 mb-4">{error}</p>
            <button
              onClick={fetchProducts}
              className="bg-[#e41e5b] text-white px-6 py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              Try Again
            </button>
          </div>
        ) : filteredProducts.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-[#746354]/10">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Product</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Price</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Stock</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Status</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#746354]/10">
                {filteredProducts.map((product) => (
                  <tr key={product.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <div className="flex items-center">
                        <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center mr-4">
                          <FiPackage className="h-6 w-6 text-[#e41e5b]" />
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[#2c2c2c]">{product.name}</div>
                          <div className="text-sm text-[#746354]">{product.description}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-sm font-semibold text-[#e41e5b]">
                        {formatCurrency(product.price)}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                        product.stock === 0 ? 'bg-red-100 text-red-800' :
                        product.stock < 10 ? 'bg-yellow-100 text-yellow-800' :
                        'bg-green-100 text-green-800'
                      }`}>
                        {product.stock} units
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Active
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => handleEdit(product)}
                          className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors"
                          title="Edit product"
                        >
                          <FiEdit className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(product.id)}
                          className="p-2 text-[#746354] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Delete product"
                        >
                          <FiTrash className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <FiPackage className="h-16 w-16 text-[#746354]/40 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">No products found</h3>
            <p className="text-[#746354] mb-6">
              {searchTerm 
                ? 'Try adjusting your search criteria'
                : 'Get started by adding your first product'
              }
            </p>
            <button
              onClick={() => setShowAddModal(true)}
              className="bg-[#e41e5b] text-white px-6 py-3 rounded-xl hover:bg-[#9a0864] transition-colors"
            >
              Add Product
            </button>
          </div>
        )}
      </div>

      {/* Add/Edit Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full p-6">
            <h2 className="text-xl font-semibold text-[#2c2c2c] mb-4">
              {editingProduct ? 'Edit Product' : 'Add Product'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Product Name
                </label>
                <input
                  type="text"
                  required
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Description
                </label>
                <textarea
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  rows="3"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Price (GHS)
                </label>
                <input
                  type="number"
                  step="0.01"
                  required
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.price}
                  onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Stock Quantity
                </label>
                <input
                  type="number"
                  required
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={formData.stock}
                  onChange={(e) => setFormData({ ...formData, stock: e.target.value })}
                />
              </div>
              <div className="flex space-x-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-[#e41e5b] text-white py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  {editingProduct ? 'Update' : 'Add'} Product
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowAddModal(false);
                    setEditingProduct(null);
                    setFormData({ name: '', description: '', price: '', stock: '' });
                  }}
                  className="flex-1 bg-gray-200 text-[#2c2c2c] py-2 rounded-lg hover:bg-gray-300 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductsPage;
