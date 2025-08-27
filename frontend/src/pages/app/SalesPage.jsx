import React, { useState, useEffect } from 'react';
import { FiPlus, FiEye, FiTrash, FiSearch, FiDollarSign, FiAlertCircle, FiCalendar, FiUser } from 'react-icons/fi';
import { salesAPI, customersAPI, productsAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

const SalesPage = () => {
  const { user } = useAuth();
  const [sales, setSales] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState('');
  const [selectedItems, setSelectedItems] = useState([]);
  const [paymentMethod, setPaymentMethod] = useState('cash');

  const fetchSales = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await salesAPI.getAll();
      if (response.data.success) {
        setSales(response.data.data);
      } else {
        setError('Failed to load sales');
      }
    } catch (err) {
      setError('Error loading sales');
      console.error('Sales error:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchCustomers = async () => {
    try {
      const response = await customersAPI.getAll();
      if (response.data.success) {
        setCustomers(response.data.data);
      }
    } catch (err) {
      console.error('Customers error:', err);
    }
  };

  const fetchProducts = async () => {
    try {
      const response = await productsAPI.getAll();
      if (response.data.success) {
        setProducts(response.data.data);
      }
    } catch (err) {
      console.error('Products error:', err);
    }
  };

  useEffect(() => {
    fetchSales();
    fetchCustomers();
    fetchProducts();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (selectedItems.length === 0) {
      alert('Please add at least one item to the sale');
      return;
    }

    try {
      const saleData = {
        customer_id: selectedCustomer || null,
        items: selectedItems,
        payment_method: paymentMethod,
        notes: ''
      };
      await salesAPI.create(saleData);
      setShowAddModal(false);
      setSelectedCustomer('');
      setSelectedItems([]);
      setPaymentMethod('cash');
      fetchSales();
    } catch (err) {
      console.error('Sale save error:', err);
    }
  };

  const handleDelete = async (saleId) => {
    if (window.confirm('Are you sure you want to delete this sale?')) {
      try {
        await salesAPI.delete(saleId);
        fetchSales();
      } catch (err) {
        console.error('Delete error:', err);
      }
    }
  };

  const addItem = () => {
    setSelectedItems([...selectedItems, { product_id: '', quantity: 1, unit_price: 0 }]);
  };

  const removeItem = (index) => {
    setSelectedItems(selectedItems.filter((_, i) => i !== index));
  };

  const updateItem = (index, field, value) => {
    const newItems = [...selectedItems];
    newItems[index][field] = value;
    
    // Auto-calculate unit price if product is selected
    if (field === 'product_id') {
      const product = products.find(p => p.id === value);
      if (product) {
        newItems[index].unit_price = product.price;
      }
    }
    
    setSelectedItems(newItems);
  };

  const calculateTotal = () => {
    return selectedItems.reduce((total, item) => {
      return total + (item.quantity * item.unit_price);
    }, 0);
  };

  const filteredSales = sales.filter(sale => {
    const customerName = sale.first_name && sale.last_name 
      ? `${sale.first_name} ${sale.last_name}`.toLowerCase()
      : '';
    return customerName.includes(searchTerm.toLowerCase()) ||
           sale.id.toLowerCase().includes(searchTerm.toLowerCase());
  });

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading sales...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Sales</h1>
            <p className="text-[#746354] mt-1">
              Manage your sales transactions and orders
            </p>
          </div>
          <button
            onClick={() => setShowAddModal(true)}
            className="flex items-center px-6 py-3 bg-[#e41e5b] text-white rounded-xl hover:bg-[#9a0864] transition-colors shadow-sm"
          >
            <FiPlus className="h-5 w-5 mr-2" />
            New Sale
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6 mb-6">
        <div className="relative">
          <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-[#746354]" />
          <input
            type="text"
            placeholder="Search sales by customer name or sale ID..."
            className="w-full pl-10 pr-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Sales Grid */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 overflow-hidden">
        {error ? (
          <div className="p-8 text-center">
            <FiAlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Sales</h3>
            <p className="text-red-600 mb-4">{error}</p>
            <button
              onClick={fetchSales}
              className="bg-[#e41e5b] text-white px-6 py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              Try Again
            </button>
          </div>
        ) : filteredSales.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-[#746354]/10">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Sale ID</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Customer</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Amount</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Payment</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Date</th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-[#2c2c2c]">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#746354]/10">
                {filteredSales.map((sale) => (
                  <tr key={sale.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <div className="text-sm font-semibold text-[#2c2c2c]">
                        #{sale.id.slice(-8)}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center">
                        <div className="w-8 h-8 bg-[#a67c00]/10 rounded-lg flex items-center justify-center mr-3">
                          <FiUser className="h-4 w-4 text-[#a67c00]" />
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[#2c2c2c]">
                            {sale.first_name && sale.last_name 
                              ? `${sale.first_name} ${sale.last_name}`
                              : 'Walk-in Customer'
                            }
                          </div>
                          <div className="text-sm text-[#746354]">
                            {sale.item_count || 0} items
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-sm font-semibold text-[#e41e5b]">
                        {formatCurrency(sale.total_amount)}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 capitalize">
                        {sale.payment_method || 'cash'}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center text-sm text-[#746354]">
                        <FiCalendar className="h-4 w-4 mr-2" />
                        {formatDate(sale.created_at)}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-2">
                        <button
                          className="p-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/10 rounded-lg transition-colors"
                          title="View sale details"
                        >
                          <FiEye className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(sale.id)}
                          className="p-2 text-[#746354] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Delete sale"
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
            <FiDollarSign className="h-16 w-16 text-[#746354]/40 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">No sales found</h3>
            <p className="text-[#746354] mb-6">
              {searchTerm 
                ? 'Try adjusting your search criteria'
                : 'Get started by creating your first sale'
              }
            </p>
            <button
              onClick={() => setShowAddModal(true)}
              className="bg-[#e41e5b] text-white px-6 py-3 rounded-xl hover:bg-[#9a0864] transition-colors"
            >
              New Sale
            </button>
          </div>
        )}
      </div>

      {/* Add Sale Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl shadow-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h2 className="text-xl font-semibold text-[#2c2c2c] mb-4">New Sale</h2>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Customer Selection */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Customer (Optional)
                </label>
                <select
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={selectedCustomer}
                  onChange={(e) => setSelectedCustomer(e.target.value)}
                >
                  <option value="">Walk-in Customer</option>
                  {customers.map((customer) => (
                    <option key={customer.id} value={customer.id}>
                      {customer.first_name} {customer.last_name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Items */}
              <div>
                <div className="flex items-center justify-between mb-4">
                  <label className="block text-sm font-medium text-[#2c2c2c]">
                    Items
                  </label>
                  <button
                    type="button"
                    onClick={addItem}
                    className="flex items-center px-3 py-1 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors text-sm"
                  >
                    <FiPlus className="h-4 w-4 mr-1" />
                    Add Item
                  </button>
                </div>
                
                {selectedItems.map((item, index) => (
                  <div key={index} className="grid grid-cols-12 gap-3 mb-3 items-end">
                    <div className="col-span-5">
                      <select
                        required
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={item.product_id}
                        onChange={(e) => updateItem(index, 'product_id', e.target.value)}
                      >
                        <option value="">Select Product</option>
                        {products.map((product) => (
                          <option key={product.id} value={product.id}>
                            {product.name} - {formatCurrency(product.price)}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="col-span-2">
                      <input
                        type="number"
                        min="1"
                        required
                        placeholder="Qty"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={item.quantity}
                        onChange={(e) => updateItem(index, 'quantity', parseInt(e.target.value))}
                      />
                    </div>
                    <div className="col-span-3">
                      <input
                        type="number"
                        step="0.01"
                        required
                        placeholder="Price"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={item.unit_price}
                        onChange={(e) => updateItem(index, 'unit_price', parseFloat(e.target.value))}
                      />
                    </div>
                    <div className="col-span-1 text-sm font-semibold text-[#e41e5b]">
                      {formatCurrency(item.quantity * item.unit_price)}
                    </div>
                    <div className="col-span-1">
                      <button
                        type="button"
                        onClick={() => removeItem(index)}
                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      >
                        <FiTrash className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              {/* Payment Method */}
              <div>
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Payment Method
                </label>
                <select
                  className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                  value={paymentMethod}
                  onChange={(e) => setPaymentMethod(e.target.value)}
                >
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="mobile_money">Mobile Money</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>

              {/* Total */}
              <div className="border-t border-[#746354]/20 pt-4">
                <div className="flex justify-between items-center text-lg font-semibold text-[#2c2c2c]">
                  <span>Total:</span>
                  <span className="text-[#e41e5b]">{formatCurrency(calculateTotal())}</span>
                </div>
              </div>

              {/* Actions */}
              <div className="flex space-x-3 pt-4">
                <button
                  type="submit"
                  className="flex-1 bg-[#e41e5b] text-white py-3 rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  Complete Sale
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setShowAddModal(false);
                    setSelectedCustomer('');
                    setSelectedItems([]);
                    setPaymentMethod('cash');
                  }}
                  className="flex-1 bg-gray-200 text-[#2c2c2c] py-3 rounded-lg hover:bg-gray-300 transition-colors"
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

export default SalesPage;
