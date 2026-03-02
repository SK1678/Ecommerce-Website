# Project Documentation: ERD and DFD

## 1. Entity Relationship Diagram (ERD)

The following diagram represents the Entity Relationship Diagram for the project. It shows the entities (tables) and their relationships within the database.

### ERD Visual Description (Mermaid)

```mermaid
erDiagram
    ACCOUNTS ||--o{ ORDERS : places
    ACCOUNTS ||--o{ CART : "manages items in"
    ACCOUNTS ||--o{ WISHLIST : "saves items to"
    
    ORDERS ||--|{ ORDER-DETAILS : contains
    ORDER-DETAILS }|--|| PRODUCTS : includes
    
    PRODUCTS ||--o{ CART : "added to"
    PRODUCTS ||--o{ WISHLIST : "added to"
    PRODUCTS ||--o{ REVIEWS : "receives"
    
    ORDERS ||--o{ REVIEWS : "has"
    
    ACCOUNTS {
        int aid PK
        string afname
        string alname
        string phone
        string email
        string cnic
        date dob
        string username
        string gender
        string password
    }
    
    PRODUCTS {
        int pid PK
        string pname
        string category
        string description
        int price
        int qtyavail
        string img
        string brand
    }
    
    ORDERS {
        int oid PK
        date dateod
        date datedel
        int aid FK
        string address
        string city
        string country
        string account
        int total
        string status
        string payment_method
        string payment_status
    }
    
    ORDER-DETAILS {
        int oid PK,FK
        int pid PK,FK
        int qty
    }
    
    CART {
        int aid PK,FK
        int pid PK,FK
        int cqty
    }
    
    WISHLIST {
        int aid PK,FK
        int pid PK,FK
    }
    
    REVIEWS {
        int oid PK,FK
        int pid PK,FK
        string rtext
        int rating
    }
```

### Table Descriptions

1.  **ACCOUNTS**: Stores user information (customers and admins).
2.  **PRODUCTS**: Contains details about items available for sale.
3.  **ORDERS**: Records order transactions, delivery details, and status.
4.  **ORDER-DETAILS**: Maps products to specific orders with quantities.
5.  **CART**: Temporary storage for products a user intends to buy.
6.  **WISHLIST**: Stores products saved by users for later.
7.  **REVIEWS**: Holds user feedback and ratings for ordered products.

---

## 2. Data Flow Diagram (DFD)

The Data Flow Diagram (DFD) maps out the flow of information for any process or system. It uses defined symbols like rectangles, circles, and arrows to show data inputs, outputs, storage points, and the routes between each destination.

### DFD Level 0 (Context Diagram)

This diagram shows the system boundaries and interactions with external entities.

```mermaid
graph TD
    User[Customer/User] -->|Registration Info, Login Creds, Order Info| System(E-commerce System)
    Admin[Admin] -->|Product Updates, Order Status Updates| System
    PaymentGateway[Payment Gateway] -->|Payment Confirmation| System
    
    System -->|Product Info, Order Status, Invoices| User
    System -->|Sales Reports, Customer Data| Admin
    System -->|Transaction Request| PaymentGateway
```

### DFD Level 1 (Process Breakdown)

This diagram breaks down the main system into specific subprocesses.

```mermaid
graph TD
    %% Entities
    User[User]
    Admin[Admin]
    DB[(Database)]
    
    %% Processes
    P1(Authentication)
    P2(Product Browsing)
    P3(Cart Management)
    P4(Order Processing)
    P5(Admin Management)
    
    %% Flows
    User -->|Login/Register| P1
    P1 <-->|Verify Credentials| DB
    P1 -->|Session Token| User
    
    User -->|Search/View| P2
    P2 <-->|Fetch Products| DB
    P2 -->|Product Details| User
    
    User -->|Add/Remove Items| P3
    P3 <-->|Update Cart| DB
    
    User -->|Checkout & Pay| P4
    P4 -->|Create Order| DB
    P4 -->|Invoice/Confirmation| User
    
    Admin -->|Add Product/Update Order| P5
    P5 <-->|Update Records| DB
```

### Process Descriptions (Level 1)

1.  **Authentication**: Handles user signup, login, and session management. Verifies credentials against the `accounts` table.
2.  **Product Browsing**: Allows users to view products by category or search. Fetches data from the `products` table.
3.  **Cart Management**: Users add or remove items. Updates the `cart` table.
4.  **Order Processing**:
    *   Validates cart items.
    *   Calculates totals.
    *   Processes payment (Stripe/bKash/COD).
    *   Creates records in `orders` and `order-details`.
    *   Updates product inventory in `products`.
5.  **Admin Management**: Admin users manage inventory, update order statuses (e.g., Shipped, Delivered), and view customer data.
