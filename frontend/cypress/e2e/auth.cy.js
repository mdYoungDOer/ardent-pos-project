describe('Authentication Flow', () => {
  beforeEach(() => {
    cy.visit('/');
  });

  it('should redirect to login when not authenticated', () => {
    cy.visit('/app/dashboard');
    cy.url().should('include', '/login');
  });

  it('should display login form', () => {
    cy.visit('/login');
    cy.get('[data-cy=email-input]').should('be.visible');
    cy.get('[data-cy=password-input]').should('be.visible');
    cy.get('[data-cy=login-button]').should('be.visible');
  });

  it('should show validation errors for empty fields', () => {
    cy.visit('/login');
    cy.get('[data-cy=login-button]').click();
    cy.contains('Email is required').should('be.visible');
    cy.contains('Password is required').should('be.visible');
  });

  it('should show error for invalid credentials', () => {
    cy.visit('/login');
    cy.get('[data-cy=email-input]').type('invalid@example.com');
    cy.get('[data-cy=password-input]').type('wrongpassword');
    cy.get('[data-cy=login-button]').click();
    
    cy.contains('Invalid credentials').should('be.visible');
  });

  it('should login successfully with valid credentials', () => {
    // Mock successful login API response
    cy.intercept('POST', `${Cypress.env('apiUrl')}/auth/login`, {
      statusCode: 200,
      body: {
        token: 'mock-jwt-token',
        user: {
          id: '1',
          name: 'Test User',
          email: 'test@example.com',
          role: 'admin'
        }
      }
    }).as('loginRequest');

    cy.visit('/login');
    cy.get('[data-cy=email-input]').type('test@example.com');
    cy.get('[data-cy=password-input]').type('password123');
    cy.get('[data-cy=login-button]').click();

    cy.wait('@loginRequest');
    cy.url().should('include', '/app/dashboard');
  });

  it('should logout successfully', () => {
    // Login first
    cy.window().its('localStorage').invoke('setItem', 'auth_token', 'mock-token');
    cy.window().its('localStorage').invoke('setItem', 'user', JSON.stringify({
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      role: 'admin'
    }));

    cy.visit('/app/dashboard');
    
    // Mock logout API response
    cy.intercept('POST', `${Cypress.env('apiUrl')}/auth/logout`, {
      statusCode: 200,
      body: { message: 'Logged out successfully' }
    }).as('logoutRequest');

    cy.get('[data-cy=user-menu]').click();
    cy.get('[data-cy=logout-button]').click();

    cy.wait('@logoutRequest');
    cy.url().should('include', '/login');
    cy.window().its('localStorage').invoke('getItem', 'auth_token').should('be.null');
  });

  it('should display registration form', () => {
    cy.visit('/register');
    cy.get('[data-cy=business-name-input]').should('be.visible');
    cy.get('[data-cy=name-input]').should('be.visible');
    cy.get('[data-cy=email-input]').should('be.visible');
    cy.get('[data-cy=password-input]').should('be.visible');
    cy.get('[data-cy=register-button]').should('be.visible');
  });

  it('should register successfully', () => {
    // Mock successful registration API response
    cy.intercept('POST', `${Cypress.env('apiUrl')}/auth/register`, {
      statusCode: 201,
      body: {
        message: 'Registration successful',
        token: 'mock-jwt-token',
        user: {
          id: '1',
          name: 'New User',
          email: 'newuser@example.com',
          role: 'admin'
        }
      }
    }).as('registerRequest');

    cy.visit('/register');
    cy.get('[data-cy=business-name-input]').type('Test Business');
    cy.get('[data-cy=name-input]').type('New User');
    cy.get('[data-cy=email-input]').type('newuser@example.com');
    cy.get('[data-cy=password-input]').type('password123');
    cy.get('[data-cy=register-button]').click();

    cy.wait('@registerRequest');
    cy.url().should('include', '/app/dashboard');
  });
});
