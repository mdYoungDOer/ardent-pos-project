import React, { useState, useEffect, useRef } from 'react';
import { 
  FiSearch, FiPlus, FiMinus, FiTrash, FiDollarSign, FiCreditCard, 
  FiUser, FiShoppingCart, FiX, FiPrinter, FiCalculator, FiClock,
  FiPackage, FiTag, FiAlertCircle, FiCheck, FiArrowLeft, FiArrowRight
} from 'react-icons/fi';
import { productsAPI, customersAPI, salesAPI } from '../../services/api';
import useAuthStore from '../../stores/authStore';

const POSPage = () => {
  const { user } = useAuthStore();
  const [products, setProducts] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [cart, setCart] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [cashReceived, setCashReceived] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showCustomerModal, setShowCustomerModal] = useState(false);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [categories, setCategories] = useState([]);
  const searchInputRef = useRef(null);

  // Fetch data on component mount
  useEffect(() => {
    fetchProducts();
    fetchCustomers();
    fetchCategories();
  }, []);

  // Filter products based on search and category
  useEffect(() => {
    let filtered = products;
    
    if (searchTerm) {
      filtered = filtered.filter(product => 
        product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        product.sku?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        product.barcode?.includes(searchTerm)
      );
    }
    
    if (selectedCategory !== 'all') {
      filtered = filtered.filter(product => product.category_id === selectedCategory);
    }
    
    setFilteredProducts(filtered);
  }, [products, searchTerm, selectedCategory]);

  const fetchProducts = async () => {
    try {
      const response = await productsAPI.getAll();
      if (response.data.success) {
        setProducts(response.data.data);
      }
    } catch (err) {
      console.error('Error fetching products:', err);
    }
  };

  const fetchCustomers = async () => {
    try {
      const response = await customersAPI.getAll();
      if (response.data.success) {
        setCustomers(response.data.data);
      }
    } catch (err) {
      console.error('Error fetching customers:', err);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await fetch('/api/categories');
      const data = await response.json();
      if (data.success) {
        setCategories(data.data);
      }
    } catch (err) {
      console.error('Error fetching categories:', err);
    }
  };

  // Cart management functions
  const addToCart = (product) => {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
      setCart(cart.map(item => 
        item.id === product.id 
          ? { ...item, quantity: item.quantity + 1 }
          : item
      ));
    } else {
      setCart([...cart, { 
        ...product, 
        quantity: 1,
        unit_price: product.price 
      }]);
    }
    
    // Clear search after adding to cart
    setSearchTerm('');
    if (searchInputRef.current) {
      searchInputRef.current.focus();
    }
  };

  const updateCartItem = (productId, field, value) => {
    setCart(cart.map(item => 
      item.id === productId 
        ? { ...item, [field]: value }
        : item
    ));
  };

  const removeFromCart = (productId) => {
    setCart(cart.filter(item => item.id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setSelectedCustomer(null);
    setPaymentMethod('cash');
    setCashReceived('');
  };

  // Calculate totals
  const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
  const tax = subtotal * 0.15; // 15% VAT
  const total = subtotal + tax;
  const change = cashReceived ? parseFloat(cashReceived) - total : 0;

  // Process sale
  const processSale = async () => {
    if (cart.length === 0) {
      setError('Cart is empty');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const saleData = {
        customer_id: selectedCustomer?.id || null,
        items: cart.map(item => ({
          product_id: item.id,
          quantity: item.quantity,
          unit_price: item.unit_price
        })),
        payment_method: paymentMethod,
        subtotal: subtotal,
        tax_amount: tax,
        total_amount: total,
        notes: `Processed by ${user.name}`
      };

      const response = await salesAPI.create(saleData);
      
      if (response.data.success) {
        // Print receipt
        printReceipt(response.data.data);
        
        // Clear cart and show success
        clearCart();
        setShowPaymentModal(false);
        
        // Show success message
        alert('Sale completed successfully!');
      }
    } catch (err) {
      setError('Failed to process sale');
      console.error('Sale error:', err);
    } finally {
      setLoading(false);
    }
  };

  // Print receipt
  const printReceipt = (sale) => {
    const receiptWindow = window.open('', '_blank');
    receiptWindow.document.write(`
      <html>
        <head>
          <title>Receipt</title>
          <style>
            body { font-family: monospace; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .item { display: flex; justify-content: space-between; margin: 5px 0; }
            .total { border-top: 1px solid #000; margin-top: 10px; padding-top: 10px; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; }
          </style>
        </head>
        <body>
          <div class="header">
            <h2>Ardent POS</h2>
            <p>Receipt #${sale.id.slice(-8)}</p>
            <p>${new Date().toLocaleString()}</p>
            <p>Cashier: ${user.name}</p>
          </div>
          
          <div class="items">
            ${cart.map(item => `
              <div class="item">
                <span>${item.name} x${item.quantity}</span>
                <span>₵${(item.quantity * item.unit_price).toFixed(2)}</span>
              </div>
            `).join('')}
          </div>
          
          <div class="total">
            <div class="item">
              <span>Subtotal:</span>
              <span>₵${subtotal.toFixed(2)}</span>
            </div>
            <div class="item">
              <span>VAT (15%):</span>
              <span>₵${tax.toFixed(2)}</span>
            </div>
            <div class="item">
              <strong>Total:</strong>
              <strong>₵${total.toFixed(2)}</strong>
            </div>
            <div class="item">
              <span>Payment Method:</span>
              <span>${paymentMethod.toUpperCase()}</span>
            </div>
            ${cashReceived ? `
              <div class="item">
                <span>Cash Received:</span>
                <span>₵${parseFloat(cashReceived).toFixed(2)}</span>
              </div>
              <div class="item">
                <span>Change:</span>
                <span>₵${change.toFixed(2)}</span>
              </div>
            ` : ''}
          </div>
          
          <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>${selectedCustomer ? `Customer: ${selectedCustomer.first_name} ${selectedCustomer.last_name}` : 'Walk-in Customer'}</p>
          </div>
        </body>
      </html>
    `);
    receiptWindow.document.close();
    receiptWindow.print();
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  return (
    <div className="h-screen bg-gray-100 flex">
      {/* Left Panel - Products */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <div className="bg-white border-b border-gray-200 p-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-[#2c2c2c]">POS Terminal</h1>
              <p className="text-[#746354]">Cashier: {user.name}</p>
            </div>
            <div className="flex items-center space-x-4">
              <div className="text-right">
                <p className="text-sm text-[#746354]">Current Time</p>
                <p className="font-semibold text-[#2c2c2c]">
                  {new Date().toLocaleTimeString()}
                </p>
              </div>
              <button
                onClick={() => window.history.back()}
                className="flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
              >
                <FiArrowLeft className="h-4 w-4 mr-2" />
                Back
              </button>
            </div>
          </div>
        </div>

        {/* Search and Categories */}
        <div className="bg-white border-b border-gray-200 p-4">
          <div className="flex space-x-4">
            <div className="flex-1 relative">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#746354]" />
              <input
                ref={searchInputRef}
                type="text"
                placeholder="Search products by name, SKU, or barcode..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                autoFocus
              />
            </div>
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
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

        {/* Products Grid */}
        <div className="flex-1 overflow-y-auto p-4">
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            {filteredProducts.map(product => (
              <div
                key={product.id}
                onClick={() => addToCart(product)}
                className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition-shadow hover:border-[#e41e5b]"
              >
                <div className="text-center">
                  <div className="w-16 h-16 bg-[#e41e5b]/10 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <FiPackage className="h-8 w-8 text-[#e41e5b]" />
                  </div>
                  <h3 className="font-semibold text-[#2c2c2c] text-sm mb-1 truncate">
                    {product.name}
                  </h3>
                  <p className="text-[#746354] text-xs mb-2">
                    {product.sku || 'No SKU'}
                  </p>
                  <p className="text-[#e41e5b] font-bold text-lg">
                    {formatCurrency(product.price)}
                  </p>
                  <div className="mt-2">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      product.stock_quantity > 10 ? 'bg-green-100 text-green-800' :
                      product.stock_quantity > 0 ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    }`}>
                      Stock: {product.stock_quantity || 0}
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {filteredProducts.length === 0 && (
            <div className="text-center py-12">
              <FiPackage className="h-12 w-12 text-[#746354] mx-auto mb-4" />
              <p className="text-[#746354]">No products found</p>
            </div>
          )}
        </div>
      </div>

      {/* Right Panel - Cart */}
      <div className="w-96 bg-white border-l border-gray-200 flex flex-col">
        {/* Cart Header */}
        <div className="p-4 border-b border-gray-200">
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-bold text-[#2c2c2c] flex items-center">
              <FiShoppingCart className="h-5 w-5 mr-2" />
              Cart ({cart.length})
            </h2>
            <button
              onClick={clearCart}
              className="text-[#746354] hover:text-red-600 transition-colors"
              title="Clear Cart"
            >
              <FiTrash className="h-5 w-5" />
            </button>
          </div>
        </div>

        {/* Cart Items */}
        <div className="flex-1 overflow-y-auto p-4">
          {cart.length === 0 ? (
            <div className="text-center py-12">
              <FiShoppingCart className="h-12 w-12 text-[#746354] mx-auto mb-4" />
              <p className="text-[#746354]">Cart is empty</p>
              <p className="text-sm text-[#746354] mt-2">Add products to get started</p>
            </div>
          ) : (
            <div className="space-y-3">
              {cart.map(item => (
                <div key={item.id} className="bg-gray-50 rounded-lg p-3">
                  <div className="flex items-center justify-between mb-2">
                    <h3 className="font-semibold text-[#2c2c2c] text-sm truncate">
                      {item.name}
                    </h3>
                    <button
                      onClick={() => removeFromCart(item.id)}
                      className="text-red-600 hover:text-red-800"
                    >
                      <FiX className="h-4 w-4" />
                    </button>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => updateCartItem(item.id, 'quantity', Math.max(1, item.quantity - 1))}
                        className="w-6 h-6 bg-[#e41e5b] text-white rounded flex items-center justify-center hover:bg-[#9a0864]"
                      >
                        <FiMinus className="h-3 w-3" />
                      </button>
                      <span className="font-semibold text-[#2c2c2c] min-w-[2rem] text-center">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() => updateCartItem(item.id, 'quantity', item.quantity + 1)}
                        className="w-6 h-6 bg-[#e41e5b] text-white rounded flex items-center justify-center hover:bg-[#9a0864]"
                      >
                        <FiPlus className="h-3 w-3" />
                      </button>
                    </div>
                    
                    <div className="text-right">
                      <p className="text-sm text-[#746354]">
                        {formatCurrency(item.unit_price)} each
                      </p>
                      <p className="font-bold text-[#e41e5b]">
                        {formatCurrency(item.quantity * item.unit_price)}
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Cart Summary */}
        <div className="border-t border-gray-200 p-4">
          <div className="space-y-2 mb-4">
            <div className="flex justify-between text-sm">
              <span className="text-[#746354]">Subtotal:</span>
              <span className="font-semibold">{formatCurrency(subtotal)}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-[#746354]">VAT (15%):</span>
              <span className="font-semibold">{formatCurrency(tax)}</span>
            </div>
            <div className="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
              <span className="text-[#2c2c2c]">Total:</span>
              <span className="text-[#e41e5b]">{formatCurrency(total)}</span>
            </div>
          </div>

          {/* Customer Selection */}
          <div className="mb-4">
            <div className="flex items-center justify-between mb-2">
              <label className="text-sm font-medium text-[#2c2c2c]">Customer</label>
              <button
                onClick={() => setShowCustomerModal(true)}
                className="text-[#e41e5b] hover:text-[#9a0864] text-sm"
              >
                {selectedCustomer ? 'Change' : 'Select'}
              </button>
            </div>
            {selectedCustomer ? (
              <div className="bg-gray-50 rounded-lg p-3">
                <p className="font-semibold text-[#2c2c2c]">
                  {selectedCustomer.first_name} {selectedCustomer.last_name}
                </p>
                <p className="text-sm text-[#746354]">
                  {selectedCustomer.phone || selectedCustomer.email}
                </p>
              </div>
            ) : (
              <div className="bg-gray-50 rounded-lg p-3 text-center">
                <p className="text-[#746354] text-sm">Walk-in Customer</p>
              </div>
            )}
          </div>

          {/* Checkout Button */}
          <button
            onClick={() => setShowPaymentModal(true)}
            disabled={cart.length === 0 || loading}
            className="w-full bg-[#e41e5b] text-white py-3 rounded-lg font-semibold hover:bg-[#9a0864] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
          >
            {loading ? (
              <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
            ) : (
              <>
                <FiDollarSign className="h-5 w-5 mr-2" />
                Checkout ({formatCurrency(total)})
              </>
            )}
          </button>
        </div>
      </div>

      {/* Payment Modal */}
      {showPaymentModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-[#2c2c2c]">Process Payment</h2>
              <button
                onClick={() => setShowPaymentModal(false)}
                className="text-[#746354] hover:text-[#2c2c2c]"
              >
                <FiX className="h-6 w-6" />
              </button>
            </div>

            {/* Payment Method Selection */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-[#2c2c2c] mb-3">
                Payment Method
              </label>
              <div className="grid grid-cols-2 gap-3">
                {[
                  { id: 'cash', name: 'Cash', icon: FiDollarSign },
                  { id: 'card', name: 'Card', icon: FiCreditCard },
                  { id: 'mobile_money', name: 'Mobile Money', icon: FiCreditCard },
                  { id: 'bank_transfer', name: 'Bank Transfer', icon: FiCreditCard }
                ].map(method => {
                  const Icon = method.icon;
                  return (
                    <button
                      key={method.id}
                      onClick={() => setPaymentMethod(method.id)}
                      className={`p-3 border rounded-lg flex items-center justify-center transition-colors ${
                        paymentMethod === method.id
                          ? 'border-[#e41e5b] bg-[#e41e5b]/10 text-[#e41e5b]'
                          : 'border-gray-300 hover:border-[#e41e5b]'
                      }`}
                    >
                      <Icon className="h-5 w-5 mr-2" />
                      {method.name}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Cash Payment Input */}
            {paymentMethod === 'cash' && (
              <div className="mb-6">
                <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                  Cash Received
                </label>
                <input
                  type="number"
                  step="0.01"
                  min={total}
                  value={cashReceived}
                  onChange={(e) => setCashReceived(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  placeholder={`Minimum: ${formatCurrency(total)}`}
                />
                {cashReceived && parseFloat(cashReceived) >= total && (
                  <div className="mt-2 text-sm text-green-600">
                    Change: {formatCurrency(change)}
                  </div>
                )}
              </div>
            )}

            {/* Error Display */}
            {error && (
              <div className="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
                <div className="flex items-center">
                  <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
                  <span className="text-red-800 text-sm">{error}</span>
                </div>
              </div>
            )}

            {/* Action Buttons */}
            <div className="flex space-x-3">
              <button
                onClick={() => setShowPaymentModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={processSale}
                disabled={loading || (paymentMethod === 'cash' && (!cashReceived || parseFloat(cashReceived) < total))}
                className="flex-1 px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? 'Processing...' : 'Complete Sale'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Customer Selection Modal */}
      {showCustomerModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-[#2c2c2c]">Select Customer</h2>
              <button
                onClick={() => setShowCustomerModal(false)}
                className="text-[#746354] hover:text-[#2c2c2c]"
              >
                <FiX className="h-6 w-6" />
              </button>
            </div>

            <div className="max-h-64 overflow-y-auto space-y-2">
              <button
                onClick={() => {
                  setSelectedCustomer(null);
                  setShowCustomerModal(false);
                }}
                className="w-full p-3 border border-gray-300 rounded-lg text-left hover:bg-gray-50 transition-colors"
              >
                <p className="font-semibold text-[#2c2c2c]">Walk-in Customer</p>
                <p className="text-sm text-[#746354]">No customer information</p>
              </button>
              
              {customers.map(customer => (
                <button
                  key={customer.id}
                  onClick={() => {
                    setSelectedCustomer(customer);
                    setShowCustomerModal(false);
                  }}
                  className="w-full p-3 border border-gray-300 rounded-lg text-left hover:bg-gray-50 transition-colors"
                >
                  <p className="font-semibold text-[#2c2c2c]">
                    {customer.first_name} {customer.last_name}
                  </p>
                  <p className="text-sm text-[#746354]">
                    {customer.phone || customer.email}
                  </p>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default POSPage;
