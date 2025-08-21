// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Hide fetch/XHR requests from command log
Cypress.on('window:before:load', (win) => {
  cy.stub(win.console, 'error').as('consoleError')
  cy.stub(win.console, 'warn').as('consoleWarn')
})

// Custom command to login programmatically
Cypress.Commands.add('login', (email = 'test@example.com', password = 'password123') => {
  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/auth/login`,
    body: { email, password }
  }).then((response) => {
    window.localStorage.setItem('auth_token', response.body.token)
    window.localStorage.setItem('user', JSON.stringify(response.body.user))
  })
})

// Custom command to logout
Cypress.Commands.add('logout', () => {
  window.localStorage.removeItem('auth_token')
  window.localStorage.removeItem('user')
})
