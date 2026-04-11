// database/neo4j/constraints.cypher

// ─── School ───────────────────────────────────────────
CREATE CONSTRAINT school_id_unique    FOR (sc:School) REQUIRE sc.id    IS UNIQUE;
CREATE CONSTRAINT school_email_unique FOR (sc:School) REQUIRE sc.email IS UNIQUE;
CREATE CONSTRAINT school_phone_unique FOR (sc:School) REQUIRE sc.phone IS UNIQUE;
CREATE CONSTRAINT school_name_unique FOR (sc:School) REQUIRE sc.name IS UNIQUE;


// ─── Student ──────────────────────────────────────────
CREATE CONSTRAINT student_id_unique    FOR (st:Student) REQUIRE st.id    IS UNIQUE;
CREATE CONSTRAINT student_email_unique FOR (st:Student) REQUIRE st.email IS UNIQUE;
CREATE CONSTRAINT student_phone_unique FOR (st:Student) REQUIRE st.phone IS UNIQUE;

// ─── Subject ──────────────────────────────────────────
CREATE CONSTRAINT subject_id_unique FOR (su:Subject) REQUIRE su.id IS UNIQUE;
CREATE CONSTRAINT subject_name_unique FOR (su:Subject) REQUIRE su.name IS UNIQUE;
