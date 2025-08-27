import React, { useState, useEffect, useRef, useCallback } from 'react';
import { 
  FiSearch, FiPlus, FiMinus, FiTrash, FiDollarSign, FiCreditCard, 
  FiUser, FiShoppingCart, FiX, FiPrinter, FiHash, FiClock,
  FiPackage, FiTag, FiAlertCircle, FiCheck, FiArrowLeft, FiArrowRight,
  FiPercent, FiGift, FiTag as FiCoupon, FiVolume2, FiVolumeX, FiRefreshCw,
  FiGrid, FiList, FiFilter, FiHash, FiCamera, FiEye
} from 'react-icons/fi';
import { productsAPI, customersAPI, salesAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

const POSPage = () => {
  const { user } = useAuth();
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
  const [viewMode, setViewMode] = useState('grid'); // 'grid' or 'list'
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [recentProducts, setRecentProducts] = useState([]);
  const [showScanner, setShowScanner] = useState(false);
  const [discounts, setDiscounts] = useState([]);
  const [appliedDiscounts, setAppliedDiscounts] = useState([]);
  const [coupons, setCoupons] = useState([]);
  const [appliedCoupon, setAppliedCoupon] = useState(null);
  const [couponCode, setCouponCode] = useState('');
  
  const searchInputRef = useRef(null);
  const audioRef = useRef(null);

  // Sound effect for adding items to cart
  const playAddToCartSound = useCallback(() => {
    if (soundEnabled && audioRef.current) {
      audioRef.current.play().catch(e => console.log('Audio play failed:', e));
    }
  }, [soundEnabled]);

  // Fetch data on component mount
  useEffect(() => {
    fetchProducts();
    fetchCustomers();
    fetchCategories();
    fetchDiscounts();
    fetchCoupons();
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

  const fetchDiscounts = async () => {
    try {
      const response = await fetch('/api/discounts');
      const data = await response.json();
      if (data.success) {
        setDiscounts(data.data);
      }
    } catch (err) {
      console.error('Error fetching discounts:', err);
    }
  };

  const fetchCoupons = async () => {
    try {
      const response = await fetch('/api/coupons');
      const data = await response.json();
      if (data.success) {
        setCoupons(data.data);
      }
    } catch (err) {
      console.error('Error fetching coupons:', err);
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
    
    // Add to recent products
    setRecentProducts(prev => {
      const filtered = prev.filter(p => p.id !== product.id);
      return [product, ...filtered.slice(0, 4)];
    });
    
    // Play sound effect
    playAddToCartSound();
    
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
    setAppliedDiscounts([]);
    setAppliedCoupon(null);
    setCouponCode('');
  };

  // Apply discount
  const applyDiscount = (discount) => {
    setAppliedDiscounts(prev => {
      const exists = prev.find(d => d.id === discount.id);
      if (exists) return prev;
      return [...prev, discount];
    });
  };

  // Remove discount
  const removeDiscount = (discountId) => {
    setAppliedDiscounts(prev => prev.filter(d => d.id !== discountId));
  };

  // Apply coupon
  const applyCoupon = async () => {
    if (!couponCode.trim()) return;
    
    try {
      const response = await fetch('/api/coupons/validate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: couponCode })
      });
      const data = await response.json();
      
      if (data.success) {
        setAppliedCoupon(data.data);
        setCouponCode('');
      } else {
        setError(data.message || 'Invalid coupon code');
      }
    } catch (err) {
      setError('Failed to validate coupon');
    }
  };

  // Remove coupon
  const removeCoupon = () => {
    setAppliedCoupon(null);
  };

  // Calculate totals with discounts and coupons
  const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
  
  // Apply discounts
  const discountAmount = appliedDiscounts.reduce((sum, discount) => {
    if (discount.type === 'percentage') {
      return sum + (subtotal * (discount.value / 100));
    } else {
      return sum + discount.value;
    }
  }, 0);
  
  // Apply coupon
  const couponAmount = appliedCoupon ? 
    (appliedCoupon.type === 'percentage' ? 
      (subtotal - discountAmount) * (appliedCoupon.value / 100) : 
      appliedCoupon.value) : 0;
  
  const afterDiscounts = subtotal - discountAmount - couponAmount;
  const tax = afterDiscounts * 0.15; // 15% VAT
  const total = afterDiscounts + tax;
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
        discount_amount: discountAmount,
        coupon_amount: couponAmount,
        tax_amount: tax,
        total_amount: total,
        applied_discounts: appliedDiscounts.map(d => d.id),
        applied_coupon: appliedCoupon?.id || null,
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
            ${discountAmount > 0 ? `
              <div class="item">
                <span>Discounts:</span>
                <span>-₵${discountAmount.toFixed(2)}</span>
              </div>
            ` : ''}
            ${couponAmount > 0 ? `
              <div class="item">
                <span>Coupon:</span>
                <span>-₵${couponAmount.toFixed(2)}</span>
              </div>
            ` : ''}
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
    <div className="h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex overflow-hidden">
      {/* Audio element for sound effects */}
      <audio ref={audioRef} preload="auto">
        <source src="/sounds/add-to-cart.mp3" type="audio/mpeg" />
      </audio>

      {/* Left Panel - Products */}
      <div className="flex-1 flex flex-col">
        {/* Enhanced Header */}
        <div className="bg-white shadow-sm border-b border-gray-200 p-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-3">
                <div className="w-12 h-12 bg-gradient-to-r from-[#e41e5b] to-[#9a0864] rounded-xl flex items-center justify-center">
                  <FiShoppingCart className="h-6 w-6 text-white" />
                </div>
                <div>
                  <h1 className="text-2xl font-bold text-[#2c2c2c]">POS Terminal</h1>
                  <p className="text-[#746354] flex items-center">
                    <FiUser className="h-4 w-4 mr-1" />
                    Cashier: {user?.first_name} {user?.last_name}
                  </p>
                </div>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              {/* Sound Toggle */}
              <button
                onClick={() => setSoundEnabled(!soundEnabled)}
                className={`p-2 rounded-lg transition-colors ${
                  soundEnabled 
                    ? 'bg-green-100 text-green-600 hover:bg-green-200' 
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
                title={soundEnabled ? 'Sound On' : 'Sound Off'}
              >
                {soundEnabled ? <FiVolume2 className="h-5 w-5" /> : <FiVolumeX className="h-5 w-5" />}
              </button>
              
              {/* Current Time */}
              <div className="text-right bg-gray-50 rounded-lg p-3">
                <p className="text-sm text-[#746354]">Current Time</p>
                <p className="font-semibold text-[#2c2c2c] flex items-center">
                  <FiClock className="h-4 w-4 mr-1" />
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

        {/* Enhanced Search and Controls */}
        <div className="bg-white border-b border-gray-200 p-4">
          <div className="flex space-x-4 mb-4">
            <div className="flex-1 relative">
              <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#746354]" />
              <input
                ref={searchInputRef}
                type="text"
                placeholder="Search products by name, SKU, or barcode..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-transparent"
                autoFocus
              />
            </div>
            
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="px-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-transparent"
            >
              <option value="all">All Categories</option>
              {categories.map(category => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
            
            <button
              onClick={() => setShowScanner(!showScanner)}
              className="px-4 py-3 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors flex items-center"
            >
                              <FiHash className="h-4 w-4 mr-2" />
              Scanner
            </button>
          </div>
          
          {/* View Mode Toggle */}
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setViewMode('grid')}
                className={`p-2 rounded-lg transition-colors ${
                  viewMode === 'grid' 
                    ? 'bg-[#e41e5b] text-white' 
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                <FiGrid className="h-4 w-4" />
              </button>
              <button
                onClick={() => setViewMode('list')}
                className={`p-2 rounded-lg transition-colors ${
                  viewMode === 'list' 
                    ? 'bg-[#e41e5b] text-white' 
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                <FiList className="h-4 w-4" />
              </button>
            </div>
            
            {/* Recent Products */}
            {recentProducts.length > 0 && (
              <div className="flex items-center space-x-2">
                <span className="text-sm text-[#746354]">Recent:</span>
                {recentProducts.slice(0, 3).map(product => (
                  <button
                    key={product.id}
                    onClick={() => addToCart(product)}
                    className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors"
                  >
                    {product.name}
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Enhanced Products Display */}
        <div className="flex-1 overflow-y-auto p-4">
          {viewMode === 'grid' ? (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
              {filteredProducts.map(product => (
                <div
                  key={product.id}
                  onClick={() => addToCart(product)}
                  className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-lg transition-all duration-200 hover:border-[#e41e5b] hover:scale-105 group"
                >
                  <div className="text-center">
                    <div className="w-16 h-16 bg-gradient-to-br from-[#e41e5b]/10 to-[#9a0864]/10 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
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
          ) : (
            <div className="space-y-3">
              {filteredProducts.map(product => (
                <div
                  key={product.id}
                  onClick={() => addToCart(product)}
                  className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer hover:shadow-md transition-all hover:border-[#e41e5b] group"
                >
                  <div className="flex items-center space-x-4">
                    <div className="w-12 h-12 bg-gradient-to-br from-[#e41e5b]/10 to-[#9a0864]/10 rounded-lg flex items-center justify-center">
                      <FiPackage className="h-6 w-6 text-[#e41e5b]" />
                    </div>
                    <div className="flex-1">
                      <h3 className="font-semibold text-[#2c2c2c]">{product.name}</h3>
                      <p className="text-[#746354] text-sm">{product.sku || 'No SKU'}</p>
                    </div>
                    <div className="text-right">
                      <p className="text-[#e41e5b] font-bold text-lg">
                        {formatCurrency(product.price)}
                      </p>
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
          )}
          
          {filteredProducts.length === 0 && (
            <div className="text-center py-12">
              <FiPackage className="h-12 w-12 text-[#746354] mx-auto mb-4" />
              <p className="text-[#746354]">No products found</p>
            </div>
          )}
        </div>
      </div>

      {/* Enhanced Right Panel - Cart */}
      <div className="w-96 bg-white border-l border-gray-200 flex flex-col">
        {/* Enhanced Cart Header */}
        <div className="p-6 border-b border-gray-200 bg-gradient-to-r from-[#e41e5b] to-[#9a0864] text-white">
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-bold flex items-center">
              <FiShoppingCart className="h-6 w-6 mr-2" />
              Cart ({cart.length})
            </h2>
            <button
              onClick={clearCart}
              className="text-white/80 hover:text-white transition-colors"
              title="Clear Cart"
            >
              <FiTrash className="h-5 w-5" />
            </button>
          </div>
        </div>

        {/* Enhanced Cart Items */}
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
                <div key={item.id} className="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="font-semibold text-[#2c2c2c] text-sm truncate">
                      {item.name}
                    </h3>
                    <button
                      onClick={() => removeFromCart(item.id)}
                      className="text-red-600 hover:text-red-800 transition-colors"
                    >
                      <FiX className="h-4 w-4" />
                    </button>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => updateCartItem(item.id, 'quantity', Math.max(1, item.quantity - 1))}
                        className="w-8 h-8 bg-[#e41e5b] text-white rounded-lg flex items-center justify-center hover:bg-[#9a0864] transition-colors"
                      >
                        <FiMinus className="h-3 w-3" />
                      </button>
                      <span className="font-semibold text-[#2c2c2c] min-w-[2rem] text-center">
                        {item.quantity}
                      </span>
                      <button
                        onClick={() => updateCartItem(item.id, 'quantity', item.quantity + 1)}
                        className="w-8 h-8 bg-[#e41e5b] text-white rounded-lg flex items-center justify-center hover:bg-[#9a0864] transition-colors"
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

        {/* Enhanced Cart Summary */}
        <div className="border-t border-gray-200 p-4 bg-gray-50">
          {/* Discounts Section */}
          <div className="mb-4">
            <div className="flex items-center justify-between mb-2">
              <label className="text-sm font-medium text-[#2c2c2c] flex items-center">
                <FiPercent className="h-4 w-4 mr-1" />
                Discounts
              </label>
              <button
                onClick={() => setShowDiscountModal(true)}
                className="text-[#e41e5b] hover:text-[#9a0864] text-sm"
              >
                Add
              </button>
            </div>
            {appliedDiscounts.length > 0 ? (
              <div className="space-y-2">
                {appliedDiscounts.map(discount => (
                  <div key={discount.id} className="flex items-center justify-between bg-green-50 rounded-lg p-2">
                    <span className="text-sm text-green-700">{discount.name}</span>
                    <button
                      onClick={() => removeDiscount(discount.id)}
                      className="text-red-600 hover:text-red-800"
                    >
                      <FiX className="h-4 w-4" />
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-[#746354]">No discounts applied</p>
            )}
          </div>

          {/* Coupon Section */}
          <div className="mb-4">
            <div className="flex items-center justify-between mb-2">
              <label className="text-sm font-medium text-[#2c2c2c] flex items-center">
                <FiCoupon className="h-4 w-4 mr-1" />
                Coupon
              </label>
              {appliedCoupon && (
                <button
                  onClick={removeCoupon}
                  className="text-red-600 hover:text-red-800 text-sm"
                >
                  Remove
                </button>
              )}
            </div>
            {appliedCoupon ? (
              <div className="bg-blue-50 rounded-lg p-3">
                <p className="font-semibold text-blue-700">{appliedCoupon.code}</p>
                <p className="text-sm text-blue-600">{appliedCoupon.description}</p>
              </div>
            ) : (
              <div className="flex space-x-2">
                <input
                  type="text"
                  placeholder="Enter coupon code"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                />
                <button
                  onClick={applyCoupon}
                  className="px-3 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
                >
                  Apply
                </button>
              </div>
            )}
          </div>

          {/* Enhanced Totals */}
          <div className="space-y-2 mb-4 bg-white rounded-lg p-4">
            <div className="flex justify-between text-sm">
              <span className="text-[#746354]">Subtotal:</span>
              <span className="font-semibold">{formatCurrency(subtotal)}</span>
            </div>
            {discountAmount > 0 && (
              <div className="flex justify-between text-sm text-green-600">
                <span>Discounts:</span>
                <span>-{formatCurrency(discountAmount)}</span>
              </div>
            )}
            {couponAmount > 0 && (
              <div className="flex justify-between text-sm text-blue-600">
                <span>Coupon:</span>
                <span>-{formatCurrency(couponAmount)}</span>
              </div>
            )}
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

          {/* Enhanced Checkout Button */}
          <button
            onClick={() => setShowPaymentModal(true)}
            disabled={cart.length === 0 || loading}
            className="w-full bg-gradient-to-r from-[#e41e5b] to-[#9a0864] text-white py-4 rounded-lg font-semibold hover:from-[#9a0864] hover:to-[#e41e5b] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105"
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

      {/* Enhanced Payment Modal */}
      {showPaymentModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-[#2c2c2c]">Process Payment</h2>
              <button
                onClick={() => setShowPaymentModal(false)}
                className="text-[#746354] hover:text-[#2c2c2c] transition-colors"
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
                      className={`p-4 border-2 rounded-lg flex items-center justify-center transition-all ${
                        paymentMethod === method.id
                          ? 'border-[#e41e5b] bg-[#e41e5b]/10 text-[#e41e5b] shadow-md'
                          : 'border-gray-300 hover:border-[#e41e5b] hover:bg-gray-50'
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
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-transparent"
                  placeholder={`Minimum: ${formatCurrency(total)}`}
                />
                {cashReceived && parseFloat(cashReceived) >= total && (
                  <div className="mt-2 text-sm text-green-600 font-semibold">
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
                className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={processSale}
                disabled={loading || (paymentMethod === 'cash' && (!cashReceived || parseFloat(cashReceived) < total))}
                className="flex-1 px-4 py-3 bg-gradient-to-r from-[#e41e5b] to-[#9a0864] text-white rounded-lg hover:from-[#9a0864] hover:to-[#e41e5b] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? 'Processing...' : 'Complete Sale'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Enhanced Customer Selection Modal */}
      {showCustomerModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-semibold text-[#2c2c2c]">Select Customer</h2>
              <button
                onClick={() => setShowCustomerModal(false)}
                className="text-[#746354] hover:text-[#2c2c2c] transition-colors"
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
                className="w-full p-4 border border-gray-300 rounded-lg text-left hover:bg-gray-50 transition-colors"
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
                  className="w-full p-4 border border-gray-300 rounded-lg text-left hover:bg-gray-50 transition-colors"
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
