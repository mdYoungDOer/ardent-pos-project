import React, { useState, useEffect } from 'react';
import { FiPlus, FiEdit, FiTrash, FiSearch, FiPackage, FiAlertCircle, FiTrendingUp, FiTrendingDown } from 'react-icons/fi';
import { productsAPI } from '../../services/api';
import useAuthStore from '../../stores/authStore';

const InventoryPage = () => {
  const { user } = useAuthStore();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [showAdjustModal, setShowAdjustModal] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [adjustmentData, setAdjustmentData] = useState({
    quantity: '',
    reason: '',
    type: 'add' // 'add' or 'subtract'
  });

  const fetchProducts = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await productsAPI.getAll();
      if (response.data.success) {
        setProducts(response.data.data);
      } else {
        setError('Failed to load inventory');
      }
    } catch (err) {
      setError('Error loading inventory');
      console.error('Inventory error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, []);

  const handleAdjustment = async (e) => {
    e.preventDefault();
    if (!selectedProduct) return;

    try {
      const currentStock = selectedProduct.stock || 0;
      const adjustment = parseInt(adjustmentData.quantity);
      const newStock = adjustmentData.type === 'add' 
        ? currentStock + adjustment 
        : currentStock - adjustment;

      if (newStock < 0) {
        alert('Cannot reduce stock below 0');
        return;
      }

      await productsAPI.update({
        id: selectedProduct.id,
        stock: newStock
      });

      setShowAdjustModal(false);
      setSelectedProduct(null);
      setAdjustmentData({ quantity: '', reason: '', type: 'add' });
      fetchProducts();
    } catch (err) {
      console.error('Stock adjustment error:', err);
    }
  };

  const getStockStatus = (stock) => {
    if (stock === 0) return { status: 'out', color: 'red', text: 'Out of Stock' };
    if (stock < 10) return { status: 'low', color: 'yellow', text: 'Low Stock' };
    if (stock < 50) return { status: 'medium', color: 'orange', text: 'Medium Stock' };
    return { status: 'good', color: 'green', text: 'Well Stocked' };
  };

  const filteredProducts = products.filter(product => {
    const matchesSearch = product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         (product.description && product.description.toLowerCase().includes(searchTerm.toLowerCase()));
    
    if (filterStatus === 'all') return matchesSearch;
    
    const stockStatus = getStockStatus(product.stock || 0);
    return matchesSearch && stockStatus.status === filterStatus;
  });

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const getInventoryStats = () => {
    const totalProducts = products.length;
    const outOfStock = products.filter(p => (p.stock || 0) === 0).length;
    const lowStock = products.filter(p => (p.stock || 0) > 0 && (p.stock || 0) < 10).length;
    const totalValue = products.reduce((sum, p) => sum + ((p.stock || 0) * (p.price || 0)), 0);

    return { totalProducts, outOfStock, lowStock, totalValue };
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading inventory...</span>
      </div>
    );
  }

  const stats = getInventoryStats();

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Inventory Management</h1>
            <p className="text-[#746354] mt-1">
              Monitor and manage your product stock levels
            </p>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Products</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{stats.totalProducts}</p>
            </div>
            <div className="w-12 h-12 bg-[#746354]/10 rounded-xl flex items-center justify-center">
              <FiPackage className="h-6 w-6 text-[#746354]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Out of Stock</p>
              <p className="text-2xl font-bold text-red-600">{stats.outOfStock}</p>
            </div>
            <div className="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
              <FiAlertCircle className="h-6 w-6 text-red-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Low Stock</p>
              <p className="text-2xl font-bold text-yellow-600">{stats.lowStock}</p>
            </div>
            <div className="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
              <FiTrendingDown className="h-6 w-6 text-yellow-600" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Value</p>
              <p className="text-2xl font-bold text-[#e41e5b]">{formatCurrency(stats.totalValue)}</p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiTrendingUp className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
          
          <div>
            <select
              className="w-full px-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
            >
              <option value="all">All Stock Levels</option>
              <option value="out">Out of Stock</option>
              <option value="low">Low Stock</option>
              <option value="medium">Medium Stock</option>
              <option value="good">Well Stocked</option>
            </select>
          </div>
        </div>
      </div>

      {/* Inventory Grid */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 overflow-hidden">
        {error ? (
          <div className="p-8 text-center">
            <FiAlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Inventory</h3>
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
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Stock Level</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Unit Price</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Total Value</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#746354]/10">
                {filteredProducts.map((product) => {
                  const stockStatus = getStockStatus(product.stock || 0);
                  const totalValue = (product.stock || 0) * (product.price || 0);
                  
                  return (
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
                        <div className="flex items-center">
                          <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                            stockStatus.color === 'red' ? 'bg-red-100 text-red-800' :
                            stockStatus.color === 'yellow' ? 'bg-yellow-100 text-yellow-800' :
                            stockStatus.color === 'orange' ? 'bg-orange-100 text-orange-800' :
                            'bg-green-100 text-green-800'
                          }`}>
                            {product.stock || 0} units
                          </span>
                          <span className="ml-2 text-xs text-[#746354]">{stockStatus.text}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <span className="text-sm font-semibold text-[#e41e5b]">
                          {formatCurrency(product.price)}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <span className="text-sm font-semibold text-[#2c2c2c]">
                          {formatCurrency(totalValue)}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center space-x-2">
                          <button
                            onClick={() => {
                              setSelectedProduct(product);
                              setShowAdjustModal(true);
                            }}
                            className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors"
                            title="Adjust stock"
                          >
                            <FiEdit className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="text-center py-12">
            <FiPackage className="h-16 w-16 text-[#746354]/40 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">No inventory found</h3>
            <p className="text-[#746354] mb-6">
              {searchTerm || filterStatus !== 'all'
                ? 'Try adjusting your search criteria'
                : 'No products in inventory'
              }
            </p>
          </div>
        )}
      </div>

      {/* Stock Adjustment Modal */}
      {showAdjustModal && selectedProduct && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-md w-full p-6">
            <h2 className="text-xl font-semibold text-[#2c2c2c] mb-4">Adjust Stock</h2>
            <div className="mb-4 p-4 bg-gray-50 rounded-lg">
              <div className="text-sm font-semibold text-[#2c2c2c]">{selectedProduct.name}</div>
              <div className="text-sm text-[#746354]">Current Stock: {selectedProduct.stock || 0} units</div>
            </div>
            <form onSubmit={handleAdjustment} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Adjustment Type
                </label>
                <select
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={adjustmentData.type}
                  onChange={(e) => setAdjustmentData({ ...adjustmentData, type: e.target.value })}
                >
                  <option value="add">Add Stock</option>
                  <option value="subtract">Remove Stock</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Quantity
                </label>
                <input
                  type="number"
                  min="1"
                  required
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={adjustmentData.quantity}
                  onChange={(e) => setAdjustmentData({ ...adjustmentData, quantity: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Reason (Optional)
                </label>
                <textarea
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  rows="3"
                  value={adjustmentData.reason}
                  onChange={(e) => setAdjustmentData({ ...adjustmentData, reason: e.target.value })}
                  placeholder="e.g., Restock, Damaged items, etc."
                />
              </div>
              <div className="flex space-x-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-[#e41e5b] text-white py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  Update Stock
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowAdjustModal(false);
                    setSelectedProduct(null);
                    setAdjustmentData({ quantity: '', reason: '', type: 'add' });
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

export default InventoryPage;
