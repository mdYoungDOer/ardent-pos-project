import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { HiPlus, HiShoppingCart, HiTrash, HiSearch, HiQrcode } from 'react-icons/hi'
import api from '../../services/api'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

const SalesPage = () => {
  const [cart, setCart] = useState([])
  const [customer, setCustomer] = useState(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [paymentMethod, setPaymentMethod] = useState('cash')
  const [isProcessing, setIsProcessing] = useState(false)
  const queryClient = useQueryClient()

  const { data: products, isLoading } = useQuery(
    ['products', searchTerm],
    () => api.get('/products', {
      params: { search: searchTerm, status: 'active' }
    }).then(res => res.data)
  )

  const { data: customers } = useQuery(
    'customers',
    () => api.get('/customers').then(res => res.data)
  )

  const processSale = useMutation(
    (saleData) => api.post('/sales', saleData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('dashboard')
        setCart([])
        setCustomer(null)
        setPaymentMethod('cash')
        toast.success('Sale completed successfully!')
      },
      onError: (error) => {
        toast.error(error.response?.data?.message || 'Failed to process sale')
      }
    }
  )

  const addToCart = (product) => {
    const existingItem = cart.find(item => item.id === product.id)
    if (existingItem) {
      setCart(cart.map(item =>
        item.id === product.id
          ? { ...item, quantity: item.quantity + 1 }
          : item
      ))
    } else {
      setCart([...cart, { ...product, quantity: 1 }])
    }
  }

  const updateQuantity = (productId, quantity) => {
    if (quantity <= 0) {
      removeFromCart(productId)
      return
    }
    setCart(cart.map(item =>
      item.id === productId ? { ...item, quantity } : item
    ))
  }

  const removeFromCart = (productId) => {
    setCart(cart.filter(item => item.id !== productId))
  }

  const calculateTotal = () => {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0)
  }

  const handleCheckout = async () => {
    if (cart.length === 0) {
      toast.error('Cart is empty')
      return
    }

    setIsProcessing(true)
    try {
      const saleData = {
        customer_id: customer?.id || null,
        payment_method: paymentMethod,
        items: cart.map(item => ({
          product_id: item.id,
          quantity: item.quantity,
          unit_price: item.price
        }))
      }

      await processSale.mutateAsync(saleData)
    } finally {
      setIsProcessing(false)
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
      {/* Products Section */}
      <div className="lg:col-span-2 space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <h1 className="text-2xl font-bold text-gray-900">Point of Sale</h1>
          <div className="mt-4 sm:mt-0 flex space-x-2">
            <button className="btn-outline flex items-center">
              <HiQrcode className="h-5 w-5 mr-2" />
              Scan Barcode
            </button>
          </div>
        </div>

        {/* Search */}
        <div className="bg-white shadow-sm rounded-lg p-4">
          <div className="relative">
            <HiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input
              type="text"
              placeholder="Search products by name, SKU, or barcode..."
              className="form-input pl-10"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>

        {/* Products Grid */}
        <div className="bg-white shadow-sm rounded-lg p-6">
          {products?.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {products.map((product) => (
                <div
                  key={product.id}
                  className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                  onClick={() => addToCart(product)}
                >
                  <div className="aspect-w-1 aspect-h-1 mb-3">
                    <div className="w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                      <span className="text-2xl font-bold text-gray-400">
                        {product.name.charAt(0)}
                      </span>
                    </div>
                  </div>
                  <h3 className="text-sm font-medium text-gray-900 mb-1">
                    {product.name}
                  </h3>
                  <p className="text-xs text-gray-500 mb-2">{product.sku}</p>
                  <div className="flex items-center justify-between">
                    <span className="text-lg font-bold text-primary">
                      ₦{parseFloat(product.price).toLocaleString()}
                    </span>
                    <span className="text-xs text-gray-500">
                      Stock: {product.quantity}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <p className="text-gray-500">
                {searchTerm ? 'No products found' : 'No products available'}
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Cart Section */}
      <div className="space-y-6">
        {/* Customer Selection */}
        <div className="bg-white shadow-sm rounded-lg p-4">
          <h3 className="text-lg font-medium text-gray-900 mb-3">Customer</h3>
          <select
            className="form-input"
            value={customer?.id || ''}
            onChange={(e) => {
              const selectedCustomer = customers?.find(c => c.id === e.target.value)
              setCustomer(selectedCustomer || null)
            }}
          >
            <option value="">Walk-in Customer</option>
            {customers?.map((c) => (
              <option key={c.id} value={c.id}>
                {c.first_name} {c.last_name}
              </option>
            ))}
          </select>
        </div>

        {/* Cart */}
        <div className="bg-white shadow-sm rounded-lg">
          <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 className="text-lg font-medium text-gray-900">Cart</h3>
            <HiShoppingCart className="h-5 w-5 text-gray-400" />
          </div>
          
          <div className="p-4">
            {cart.length > 0 ? (
              <div className="space-y-3 max-h-64 overflow-y-auto">
                {cart.map((item) => (
                  <div key={item.id} className="flex items-center justify-between">
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-900 truncate">
                        {item.name}
                      </p>
                      <p className="text-xs text-gray-500">
                        ₦{parseFloat(item.price).toLocaleString()} each
                      </p>
                    </div>
                    <div className="flex items-center space-x-2">
                      <div className="flex items-center">
                        <button
                          onClick={() => updateQuantity(item.id, item.quantity - 1)}
                          className="text-gray-400 hover:text-gray-600"
                        >
                          -
                        </button>
                        <span className="mx-2 text-sm font-medium">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() => updateQuantity(item.id, item.quantity + 1)}
                          className="text-gray-400 hover:text-gray-600"
                        >
                          +
                        </button>
                      </div>
                      <button
                        onClick={() => removeFromCart(item.id)}
                        className="text-red-400 hover:text-red-600"
                      >
                        <HiTrash className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <HiShoppingCart className="mx-auto h-12 w-12 text-gray-400" />
                <p className="mt-2 text-sm text-gray-500">Cart is empty</p>
              </div>
            )}
          </div>

          {cart.length > 0 && (
            <div className="border-t border-gray-200 p-4 space-y-4">
              {/* Payment Method */}
              <div>
                <label className="form-label">Payment Method</label>
                <select
                  className="form-input"
                  value={paymentMethod}
                  onChange={(e) => setPaymentMethod(e.target.value)}
                >
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="transfer">Bank Transfer</option>
                  <option value="mobile_money">Mobile Money</option>
                </select>
              </div>

              {/* Total */}
              <div className="flex justify-between items-center text-lg font-bold">
                <span>Total:</span>
                <span>₦{calculateTotal().toLocaleString()}</span>
              </div>

              {/* Checkout Button */}
              <button
                onClick={handleCheckout}
                disabled={isProcessing}
                className="btn-primary w-full flex justify-center"
              >
                {isProcessing ? (
                  <LoadingSpinner size="sm" />
                ) : (
                  'Complete Sale'
                )}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default SalesPage
