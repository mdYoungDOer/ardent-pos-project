// Custom commands for Cypress tests

// Command to seed test data
Cypress.Commands.add('seedTestData', () => {
  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/test/seed`,
    headers: {
      'Authorization': `Bearer ${window.localStorage.getItem('auth_token')}`
    }
  })
})

// Command to clean test data
Cypress.Commands.add('cleanTestData', () => {
  cy.request({
    method: 'DELETE',
    url: `${Cypress.env('apiUrl')}/test/clean`,
    headers: {
      'Authorization': `Bearer ${window.localStorage.getItem('auth_token')}`
    }
  })
})

// Command to create a test product
Cypress.Commands.add('createTestProduct', (productData = {}) => {
  const defaultProduct = {
    name: 'Test Product',
    sku: 'TEST001',
    price: 99.99,
    cost: 50.00,
    stock_quantity: 100,
    min_stock_level: 10,
    description: 'A test product'
  }

  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/products`,
    headers: {
      'Authorization': `Bearer ${window.localStorage.getItem('auth_token')}`
    },
    body: { ...defaultProduct, ...productData }
  })
})

// Command to create a test customer
Cypress.Commands.add('createTestCustomer', (customerData = {}) => {
  const defaultCustomer = {
    name: 'Test Customer',
    email: 'customer@example.com',
    phone: '+233123456789'
  }

  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/customers`,
    headers: {
      'Authorization': `Bearer ${window.localStorage.getItem('auth_token')}`
    },
    body: { ...defaultCustomer, ...customerData }
  })
})

// Command to wait for API request to complete
Cypress.Commands.add('waitForApi', (alias) => {
  cy.wait(alias).then((interception) => {
    expect(interception.response.statusCode).to.be.oneOf([200, 201, 204])
  })
})
