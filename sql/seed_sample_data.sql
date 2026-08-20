-- Phase 2 Delivery Tracker — sample data
--
-- LOCAL/DEV ONLY. Populates the pm_* tables with realistic example content
-- themed around the actual Phase 2 programme (ROCC replacing Servitor) so
-- the app is easy to explore. Do NOT run this against the production
-- database — it references real local user ids and is meant for a local
-- copy of the shared database only.
--
-- Assumes sql/schema.sql has already been run, and that users with ids
-- 1 (admin), 2, 3, 7, 8 exist in the shared `users` table (adjust the ids
-- below if your local `users` table differs).

-- ── Milestones ──────────────────────────────────────────────
INSERT INTO pm_milestones (title, phase, target_date, status, notes, created_by) VALUES
('Discovery sign-off with Programme Board', 'Discovery', '2026-01-30', 'complete', 'Phase 1 scoping and as-is/to-be mapping formally closed out.', 1),
('Supplier contract signed with ROCC', 'Procurement', '2026-03-14', 'complete', 'Contract executed following procurement evaluation.', 1),
('Solution design workshops complete', 'Solution Design', '2026-07-10', 'complete', 'Core workflow design agreed across repairs, void, and DLO processes.', 7),
('Design Authority sign-off on to-be process', 'Solution Design', '2026-09-18', 'upcoming', 'Final review of the agreed to-be process model before build starts in earnest.', 7),
('Integration spec agreed: NEC Housing', 'Build', '2026-10-02', 'at_risk', 'ROCC still awaiting sample data extract from NEC side — see risk log.', 2),
('Integration spec agreed: Integra (Finance)', 'Build', '2026-10-16', 'upcoming', NULL, 3),
('Pilot environment stood up', 'Build', '2026-11-20', 'upcoming', 'Sandbox environment for DLO pilot testing.', 1),
('Pilot go-live: single DLO team', 'Pilot', '2027-01-15', 'upcoming', 'First live use with a contained team before wider rollout.', 7),
('Full rollout complete', 'Implementation', '2027-06-30', 'upcoming', NULL, 1),
('Servitor decommissioned', 'Go-live', '2027-10-01', 'upcoming', 'Legacy system retirement — hard deadline, Servitor support ends 2027.', 1);

-- ── Tasks ───────────────────────────────────────────────────
INSERT INTO pm_tasks (title, description, assignee_user_id, status, due_date, created_by) VALUES
('Confirm ROCC discovery workshop dates for September', 'Coordinate diaries with the ROCC solution design team and DLO leads.', 7, 'in_progress', '2026-08-28', 1),
('Draft integration requirements for NEC Housing', 'Document required fields and event triggers for the repairs order interface.', 2, 'in_progress', '2026-09-05', 1),
('Review Servitor data extract for migration scoping', 'Sample 5 years of repairs history to assess data quality issues.', 3, 'todo', '2026-09-12', 1),
('Set up sandbox environment access for pilot team', 'Request user accounts and VPN access from ROCC for the pilot DLO team.', 1, 'todo', '2026-09-20', 7),
('Write mobile working requirements for DLO operatives', 'Capture offline/low-signal requirements from field visits.', 8, 'todo', '2026-09-25', 7),
('Follow up with North Ayrshire on ROCC lessons learned', 'Second call to dig into their go-live pain points before our own pilot.', 7, 'todo', NULL, 1),
('Update Design Authority pack for September review', 'Consolidate solution design decisions into a single review pack.', 7, 'in_progress', '2026-09-15', 1),
('Confirm Integra finance code mapping', 'Align repairs cost codes between Servitor and Integra.', 3, 'todo', '2026-10-10', 2),
('Draft mobile device rollout plan for pilot', 'Procurement and device management plan for the DLO pilot team.', 8, 'todo', NULL, 7),
('Schedule DPIA review for repairs data in ROCC', 'Book time with Information Governance ahead of pilot go-live.', 1, 'todo', '2026-10-30', 1),
('Close out Phase 1 documentation archive', 'Tidy and file all discovery-phase artefacts for reference.', 2, 'done', '2026-02-10', 1),
('Agree access model for read-only reporting users', 'Define who gets dashboard-only access vs. full editing.', 1, 'done', '2026-06-01', 7);

-- ── Risks & issues ──────────────────────────────────────────
INSERT INTO pm_risks_issues (type, title, description, severity, status, owner_user_id, raised_date, created_by) VALUES
('risk', 'Data migration complexity from Servitor', 'Legacy data quality is inconsistent across 15+ years of repairs history; full profiling not yet complete.', 'amber', 'open', 3, '2026-06-02', 1),
('risk', 'Staff adoption and change resistance', 'DLO operatives have used Servitor for over a decade; early engagement sessions show some resistance to a new interface.', 'amber', 'open', 8, '2026-06-15', 1),
('risk', 'Servitor contract expiry alignment', 'Legacy system becomes unsupported in 2027 — any slippage in the build timeline compresses the migration window.', 'red', 'open', 1, '2026-05-20', 1),
('issue', 'NEC Housing sample data extract delayed', 'NEC side has not yet provided the sample data needed to finalise the integration spec, blocking milestone progress.', 'red', 'open', 2, '2026-08-05', 7),
('risk', 'Mobile network coverage in rural DLO routes', 'Some repair routes have poor signal, which could affect offline-first mobile working requirements.', 'amber', 'open', 8, '2026-07-22', 7),
('risk', 'Integration complexity across five systems', 'ROCC needs to integrate with NEC Housing, Integra, iTrent, DRS and Apex — sequencing and testing all five adds schedule risk.', 'amber', 'mitigated', 1, '2026-04-10', 1),
('issue', 'Design Authority meeting cadence slipped in July', 'Two consecutive monthly Design Authority sessions were rescheduled, delaying sign-off decisions.', 'amber', 'mitigated', 7, '2026-07-01', 1),
('risk', 'Budget contingency for extended pilot phase', 'If the DLO pilot surfaces significant rework, contingency budget may be needed beyond current allocation.', 'green', 'open', 1, '2026-08-10', 1);

-- ── Decisions required ──────────────────────────────────────
INSERT INTO pm_decisions (title, description, needed_by_date, decision_owner_user_id, status, outcome, decided_date, created_by) VALUES
('Confirm pilot DLO team for phase 1 rollout', 'Need to agree which team goes live first — affects training and comms planning.', '2026-09-10', 7, 'open', NULL, NULL, 1),
('Approve mobile device standard for field operatives', 'Choose between ruggedised tablets vs. standard Android devices for DLO mobile working.', '2026-09-30', 1, 'open', NULL, NULL, 7),
('Data retention policy for migrated Servitor records', 'Decide how far back repairs history gets migrated vs. archived read-only.', '2026-10-15', 3, 'open', NULL, NULL, 1),
('Approve integration sequencing order', 'Confirm build order across NEC Housing, Integra, iTrent, DRS, Apex.', '2026-09-05', 2, 'open', NULL, NULL, 1),
('Governance ownership of Design Authority pack', 'Agreed William Ellis maintains the master pack; Programme Board reviews monthly.', '2026-07-15', 7, 'decided', 'Agreed — single owner avoids version conflicts.', '2026-07-10', 1),
('Confirm training approach for DLO operatives', 'Agreed a blended approach: in-person sessions plus short video walkthroughs.', '2026-08-01', 8, 'decided', 'Blended training approved by Programme Board.', '2026-07-28', 7);

-- ── Supplier activity (ROCC) ────────────────────────────────
INSERT INTO pm_supplier_activities (supplier, title, description, status, due_date, owner_user_id, created_by) VALUES
('ROCC', 'Solution design workshop series', 'Four workshops covering repairs, voids, DLO scheduling and reporting.', 'complete', '2026-07-10', 7, 1),
('ROCC', 'Provide NEC Housing sample data extract', 'Sample repairs order data needed to finalise integration spec.', 'blocked', '2026-08-25', 2, 1),
('ROCC', 'Draft integration spec: Integra (Finance)', 'First draft of finance system integration touchpoints.', 'in_progress', '2026-10-01', 3, 1),
('ROCC', 'Sandbox environment provisioning', 'Stand up a test environment for the pilot DLO team.', 'planned', '2026-11-10', 1, 1),
('ROCC', 'Mobile working proof of concept', 'Demonstrate offline-capable mobile app for field operatives.', 'planned', '2026-11-25', 8, 1),
('ROCC', 'iTrent (HR) integration scoping call', 'Initial scoping conversation for workforce data sync.', 'planned', NULL, 2, 1),
('ROCC', 'DPIA input for pilot go-live', 'Supplier input into the Information Governance data protection impact assessment.', 'planned', '2026-10-25', 1, 1),
('ROCC', 'Weekly delivery check-in', 'Standing weekly call between ROCC delivery lead and the council team.', 'in_progress', NULL, 7, 1);

-- ── Weekly archive ──────────────────────────────────────────
INSERT INTO pm_weekly_snapshots
  (period_label, overall_status, current_focus, progress_narrative, achievements, key_decisions, risks_raised, lessons_learned, looking_ahead_notes, created_by_user_id, created_at) VALUES
('Week of 27 Jul 2026', 'green', 'Wrapping up the solution design workshop series and preparing the Design Authority pack.',
 'Completed the fourth and final solution design workshop, covering DLO scheduling. Team is now consolidating outputs into the Design Authority review pack.',
 'All four solution design workshops delivered on schedule. Blended training approach agreed for DLO operatives.',
 'Agreed William owns the master Design Authority pack; Programme Board reviews monthly.',
 'None raised this week.',
 'Workshop attendance was much better when sessions were scheduled around DLO shift patterns rather than standard office hours.',
 'Design Authority sign-off targeted for mid-September; integration spec drafting starts in parallel.',
 7, '2026-07-31 09:15:00'),
('Week of 03 Aug 2026', 'amber', 'Starting integration spec drafting for NEC Housing and Integra.',
 'Kicked off integration scoping. ROCC flagged they are waiting on a sample data extract from the NEC Housing side before the spec can be finalised.',
 'Integration sequencing discussion started with ROCC.',
 'None this week — sequencing decision expected early September.',
 'NEC Housing sample data extract is delayed, which could push the integration spec milestone.',
 'Flagging supplier dependencies earlier in the plan would have given more runway here.',
 'Design Authority sign-off remains on track for mid-September pending this week''s pack review.',
 7, '2026-08-07 09:30:00'),
('Week of 10 Aug 2026', 'amber', 'Chasing the NEC Housing data extract; continuing DPIA groundwork for the pilot.',
 'No movement yet on the NEC Housing extract. Started early conversations with Information Governance about the DPIA needed ahead of pilot go-live.',
 'DPIA groundwork started ahead of schedule.',
 'None this week.',
 'NEC Housing extract still outstanding — now amber given proximity to the integration spec milestone.',
 NULL,
 'Next 60-90 days: Design Authority sign-off, integration spec sequencing decision, DPIA review booked.',
 7, '2026-08-14 09:00:00'),
('Week of 17 Aug 2026', 'amber', 'Finalising the Design Authority pack; escalating the NEC Housing data extract delay.',
 'Design Authority pack is in final review. Escalated the NEC Housing data delay directly with their technical lead this week — response expected by Friday.',
 'Design Authority pack drafted and circulated for comment ahead of the September review.',
 'None this week — pending Design Authority review.',
 'NEC Housing sample data extract delay escalated; now tracked as a red issue given the knock-on effect on the integration milestone.',
 'Escalating supplier delays directly with technical leads (not just account managers) got a faster response this time.',
 'Design Authority sign-off due 18 Sep. Pilot environment stand-up targeted for November, DPIA review by end of October.',
 7, '2026-08-19 09:20:00');

-- Tags: top-level System/Stakeholder/Section, each with children nested under it
INSERT INTO pm_tags (parent_id, name) VALUES
  (NULL, 'System'), (NULL, 'Stakeholder'), (NULL, 'Section');

INSERT INTO pm_tags (parent_id, name)
SELECT top.id, t.name FROM pm_tags top
JOIN (
  SELECT 'System' top_name, 'ROCC' name UNION ALL
  SELECT 'System', 'NECH' UNION ALL
  SELECT 'System', 'APEX' UNION ALL
  SELECT 'Stakeholder', 'Tenants' UNION ALL
  SELECT 'Stakeholder', 'Staff' UNION ALL
  SELECT 'Section', 'Property Services' UNION ALL
  SELECT 'Section', 'Business Support'
) t ON t.top_name = top.name
WHERE top.parent_id IS NULL;
