# Design Document - Index

**Project:** Centralized Betting Coordination System  
**Version:** 1.1  
**Last Updated:** January 27, 2026

---

## 📚 Document Structure

This folder contains the complete system design documentation extracted from the Software Requirements Specification (SRS) and Technical Documentation.

### Core Documents

#### [[1. System Overview]]
**What it covers:**
- High-level system description
- Problem statement and solution
- System architecture diagram
- Technology stack
- Project timeline
- Success criteria

**Read this if you want:**
- A quick understanding of what the system does
- The big picture before diving into details
- An overview of the project scope

---

#### [[2. Architecture]]
**What it covers:**
- Backend architecture and components
- Mobile architecture and components
- Communication protocols
- Security architecture
- Data flow diagrams
- Scalability considerations

**Read this if you want:**
- Deep technical understanding of system design
- Component interactions and responsibilities
- Implementation patterns and best practices

---

#### [[3. Database Schema]]
**What it covers:**
- Complete PostgreSQL schema
- Entity-relationship diagrams
- Table structures with constraints
- Indexes and optimization
- Common queries
- Migration scripts

**Read this if you want:**
- Database structure details
- Data relationships
- Query patterns
- Performance optimization strategies

---

#### [[4. API Specification]]
**What it covers:**
- REST API endpoints
- WebSocket events
- Request/response formats
- Authentication flow
- Error handling
- Rate limiting

**Read this if you want:**
- API integration details
- WebSocket communication patterns
- Request/response examples
- Error codes and handling

---

#### [[8. Business Rules & Algorithms]]
**What it covers:**
- Allocation engine algorithm
- IP conflict detection rules
- Proxy rotation logic
- Auto-pause conditions
- Time synchronization
- Capital management rules
- Matchup balancing

**Read this if you want:**
- Core business logic details
- Algorithm implementations
- Rule specifications
- Step-by-step examples

---

## 🗺️ Document Map by Role

### For Backend Developers
**Primary:**
1. [[2. Architecture]] - Backend components
2. [[3. Database Schema]] - Database design
3. [[8. Business Rules & Algorithms]] - Core logic
4. [[4. API Specification]] - API contracts

**Secondary:**
- [[1. System Overview]] - Context

### For Mobile Developers
**Primary:**
1. [[2. Architecture]] - Mobile components
2. [[4. API Specification]] - API integration
3. [[8. Business Rules & Algorithms]] - Time sync, execution logic

**Secondary:**
- [[1. System Overview]] - Context
- [[3. Database Schema]] - Data understanding

### For DevOps Engineers
**Primary:**
1. [[2. Architecture]] - Scalability, security
2. [[3. Database Schema]] - Performance optimization

**Secondary:**
- [[1. System Overview]] - System requirements

### For Product Managers
**Primary:**
1. [[1. System Overview]] - Complete picture
2. [[8. Business Rules & Algorithms]] - Feature logic

**Secondary:**
- [[4. API Specification]] - Technical capabilities

---

## 📖 Reading Paths

### Quick Start (30 minutes)
1. [[1. System Overview]] (10 min)
2. [[2. Architecture]] - Architecture Layers section only (10 min)
3. [[4. API Specification]] - Skim endpoints (10 min)

### Complete Understanding (3-4 hours)
1. [[1. System Overview]] (20 min)
2. [[2. Architecture]] (60 min)
3. [[3. Database Schema]] (45 min)
4. [[4. API Specification]] (45 min)
5. [[8. Business Rules & Algorithms]] (60 min)

### Implementation-Focused (2 hours)
1. [[2. Architecture]] - Component details (30 min)
2. [[3. Database Schema]] - Tables + queries (30 min)
3. [[8. Business Rules & Algorithms]] (60 min)

---

## 🔑 Key Concepts Index

### Allocation Engine
- Document: [[8. Business Rules & Algorithms#Allocation Engine]]
- Related: [[2. Architecture#Allocation Engine]]
- Implementation: Backend Phase 3

### IP Management
- Document: [[8. Business Rules & Algorithms#IP Conflict Detection]]
- Schema: [[3. Database Schema#IP Management Tables]]
- API: [[4. API Specification#IP Management API]]
- Implementation: Backend Phase 0

### Teams & Matchups
- Overview: [[1. System Overview#Key Features]]
- Architecture: [[2. Architecture#Team Management Module]]
- Schema: [[3. Database Schema#Core Tables]]
- API: [[4. API Specification#Teams API]]
- Implementation: Backend Phase 2

### Round Lifecycle
- Flow: [[1. System Overview#High-Level Flow]]
- State Machine: [[2. Architecture#Round Lifecycle Manager]]
- Schema: [[3. Database Schema#7. Rounds]]
- API: [[4. API Specification#Rounds API]]

### WebSocket Communication
- Architecture: [[2. Architecture#WebSocket Server]]
- Events: [[4. API Specification#WebSocket Events]]
- Implementation: Backend Phase 4, Mobile Week 2-3

### Time Synchronization
- Algorithm: [[8. Business Rules & Algorithms#Time Synchronization]]
- Architecture: [[2. Architecture#Time Synchronization Manager]]
- API: [[4. API Specification#Time Synchronization API]]
- Implementation: Mobile Week 3

### Capital Management
- Rules: [[8. Business Rules & Algorithms#Capital Management Rules]]
- Schema: [[3. Database Schema#3. Capitals]]
- API: [[4. API Specification#Capital Management API]]
- Auto-Pause: [[8. Business Rules & Algorithms#Auto-Pause Logic]]

### Proxy Rotation
- Algorithm: [[8. Business Rules & Algorithms#Proxy Rotation Algorithm]]
- Schema: [[3. Database Schema#12. Proxy_Pool]]
- API: [[4. API Specification#IP Management API]]

---

## 📋 Implementation Checklists

### Backend Checklist
Use these documents during implementation:

**Phase 0 (IP Management):**
- [ ] Read [[3. Database Schema#IP Management Tables]]
- [ ] Read [[8. Business Rules & Algorithms#IP Conflict Detection]]
- [ ] Read [[8. Business Rules & Algorithms#Proxy Rotation Algorithm]]

**Phase 1 (Foundation):**
- [ ] Read [[3. Database Schema#Core Tables]]
- [ ] Read [[2. Architecture#Backend Architecture]]

**Phase 2 (Teams & Matchups):**
- [ ] Read [[8. Business Rules & Algorithms#Matchup Balancing]]
- [ ] Read [[4. API Specification#Teams API]]
- [ ] Read [[4. API Specification#Matchups API]]

**Phase 3 (Allocation):**
- [ ] Read [[8. Business Rules & Algorithms#Allocation Engine]]
- [ ] Implement algorithm exactly as specified

**Phase 4 (WebSocket):**
- [ ] Read [[2. Architecture#WebSocket Server]]
- [ ] Read [[4. API Specification#WebSocket Events]]

### Mobile Checklist

**Week 1 (Foundation):**
- [ ] Read [[2. Architecture#Mobile Architecture]]
- [ ] Read [[2. Architecture#AccessibilityService]]

**Week 2 (Core Features):**
- [ ] Read [[2. Architecture#WebSocket Client]]
- [ ] Read [[4. API Specification#WebSocket Events]]

**Week 3 (Backend Integration):**
- [ ] Read [[8. Business Rules & Algorithms#Time Synchronization]]
- [ ] Read [[2. Architecture#Round Scheduler]]

**Week 4 (Provider Logic):**
- [ ] Read [[2. Architecture#Provider Adapter Pattern]]

---

## 🔄 Document Updates

### Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Jan 10, 2026 | Initial design documents created |
| 1.1 | Jan 27, 2026 | Added IP management, proxy rotation, game state detection, updated from SRS v1.1 |

### Maintenance Notes

These documents are maintained alongside:
- **SRS Document** (`srs_document.md`) - Requirements source
- **Technical Documentation** (`tech_docs_detailed.md`) - Implementation details
- **Backend Development Guide** (`Backend/Laravel Backend - Development Stages Guide.md`)
- **Mobile Development Roadmap** (`Android/Kotlin Android MVP Development Roadmap.md`)

When updating these documents, ensure consistency across all related documentation.

---

## 🔗 External References

### Related Project Documents
- [[../Backend/Laravel Backend - Development Stages Guide]]
- [[../Backend/Laravel Backend Development Timeline]]
- [[../Android/Kotlin Android MVP - Development Stages Guide]]
- [[../Android/Kotlin Android MVP Development Roadmap]]
- [[../Android/Kotlin Android MVP Development Timeline]]

### Source Documents
- `/mnt/user-data/uploads/srs_document.md` - Software Requirements Specification
- `/mnt/user-data/uploads/tech_docs_detailed.md` - Technical Documentation

---

## 💡 Quick Reference

### Common Questions

**Q: How does the allocation engine work?**  
A: See [[8. Business Rules & Algorithms#Allocation Engine]] for the complete algorithm with examples.

**Q: What are the database tables?**  
A: See [[3. Database Schema#Core Tables]] for all table structures.

**Q: How do devices connect to the server?**  
A: See [[4. API Specification#WebSocket Events]] and [[2. Architecture#WebSocket Server]].

**Q: What are the API endpoints?**  
A: See [[4. API Specification]] for complete REST API documentation.

**Q: How is capital managed?**  
A: See [[8. Business Rules & Algorithms#Capital Management Rules]] for rules and [[3. Database Schema#3. Capitals]] for schema.

**Q: How does IP conflict detection work?**  
A: See [[8. Business Rules & Algorithms#IP Conflict Detection]] for complete rules and examples.

---

## 📞 Contact

For questions about this documentation:
- Technical Lead: [Contact Info]
- Product Owner: [Contact Info]
- Documentation Maintainer: [Contact Info]

---

*This index document provides navigation through the complete system design. Start with [[1. System Overview]] for a high-level introduction.*
