import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import ProductForm from '../../components/products/ProductForm';
import * as api from '../../services/api';

// Mock the API
vi.mock('../../services/api');

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  return ({ children }) => (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        {children}
      </BrowserRouter>
    </QueryClientProvider>
  );
};

describe('ProductForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('renders product form with all fields', () => {
    render(<ProductForm onSuccess={() => {}} />, { wrapper: createWrapper() });

    expect(screen.getByLabelText(/product name/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/sku/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/price/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/cost/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/stock quantity/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/minimum stock level/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /create product/i })).toBeInTheDocument();
  });

  test('validates required fields', async () => {
    render(<ProductForm onSuccess={() => {}} />, { wrapper: createWrapper() });

    const submitButton = screen.getByRole('button', { name: /create product/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText(/product name is required/i)).toBeInTheDocument();
      expect(screen.getByText(/sku is required/i)).toBeInTheDocument();
      expect(screen.getByText(/price is required/i)).toBeInTheDocument();
    });
  });

  test('validates numeric fields', async () => {
    render(<ProductForm onSuccess={() => {}} />, { wrapper: createWrapper() });

    const priceInput = screen.getByLabelText(/price/i);
    const costInput = screen.getByLabelText(/cost/i);
    const stockInput = screen.getByLabelText(/stock quantity/i);

    fireEvent.change(priceInput, { target: { value: 'invalid' } });
    fireEvent.change(costInput, { target: { value: 'invalid' } });
    fireEvent.change(stockInput, { target: { value: 'invalid' } });

    const submitButton = screen.getByRole('button', { name: /create product/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText(/price must be a valid number/i)).toBeInTheDocument();
      expect(screen.getByText(/cost must be a valid number/i)).toBeInTheDocument();
      expect(screen.getByText(/stock quantity must be a valid number/i)).toBeInTheDocument();
    });
  });

  test('submits form with valid data', async () => {
    const mockOnSuccess = vi.fn();
    const mockApiResponse = { data: { id: '1', message: 'Product created successfully' } };
    
    api.post.mockResolvedValue(mockApiResponse);

    render(<ProductForm onSuccess={mockOnSuccess} />, { wrapper: createWrapper() });

    // Fill out the form
    fireEvent.change(screen.getByLabelText(/product name/i), { target: { value: 'Test Product' } });
    fireEvent.change(screen.getByLabelText(/sku/i), { target: { value: 'TEST001' } });
    fireEvent.change(screen.getByLabelText(/price/i), { target: { value: '99.99' } });
    fireEvent.change(screen.getByLabelText(/cost/i), { target: { value: '50.00' } });
    fireEvent.change(screen.getByLabelText(/stock quantity/i), { target: { value: '100' } });
    fireEvent.change(screen.getByLabelText(/minimum stock level/i), { target: { value: '10' } });

    const submitButton = screen.getByRole('button', { name: /create product/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(api.post).toHaveBeenCalledWith('/products', {
        name: 'Test Product',
        sku: 'TEST001',
        price: 99.99,
        cost: 50.00,
        stock_quantity: 100,
        min_stock_level: 10,
        description: ''
      });
      expect(mockOnSuccess).toHaveBeenCalled();
    });
  });

  test('handles API errors', async () => {
    const mockError = {
      response: {
        data: {
          errors: {
            sku: 'SKU already exists'
          }
        }
      }
    };

    api.post.mockRejectedValue(mockError);

    render(<ProductForm onSuccess={() => {}} />, { wrapper: createWrapper() });

    // Fill out the form
    fireEvent.change(screen.getByLabelText(/product name/i), { target: { value: 'Test Product' } });
    fireEvent.change(screen.getByLabelText(/sku/i), { target: { value: 'DUPLICATE' } });
    fireEvent.change(screen.getByLabelText(/price/i), { target: { value: '99.99' } });

    const submitButton = screen.getByRole('button', { name: /create product/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByText(/sku already exists/i)).toBeInTheDocument();
    });
  });

  test('populates form when editing existing product', () => {
    const existingProduct = {
      id: '1',
      name: 'Existing Product',
      sku: 'EXIST001',
      price: 29.99,
      cost: 15.00,
      stock_quantity: 50,
      min_stock_level: 5,
      description: 'An existing product'
    };

    render(<ProductForm product={existingProduct} onSuccess={() => {}} />, { wrapper: createWrapper() });

    expect(screen.getByDisplayValue('Existing Product')).toBeInTheDocument();
    expect(screen.getByDisplayValue('EXIST001')).toBeInTheDocument();
    expect(screen.getByDisplayValue('29.99')).toBeInTheDocument();
    expect(screen.getByDisplayValue('15')).toBeInTheDocument();
    expect(screen.getByDisplayValue('50')).toBeInTheDocument();
    expect(screen.getByDisplayValue('5')).toBeInTheDocument();
    expect(screen.getByDisplayValue('An existing product')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /update product/i })).toBeInTheDocument();
  });
});
