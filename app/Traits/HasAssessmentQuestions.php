<?php

namespace App\Traits;

trait HasAssessmentQuestions
{
    protected array $phiPillars = [
        'P1' => ['name' => 'Governance & Decision-Making', 'weight' => 1.5, 'critical' => true],
        'P2' => ['name' => 'Planning, Scope & Delivery Control', 'weight' => 1.5, 'critical' => true],
        'P3' => ['name' => 'Business Alignment, Value & Financial Control', 'weight' => 1.4, 'critical' => true],
        'P4' => ['name' => 'Change, Training & Adoption', 'weight' => 1.2, 'critical' => false],
        'P5' => ['name' => 'Data Readiness & Migration', 'weight' => 1.2, 'critical' => false],
        'P6' => ['name' => 'Solution, Architecture & Process Fit', 'weight' => 1.2, 'critical' => false],
        'P7' => ['name' => 'Cutover & Go-Live Readiness', 'weight' => 1.3, 'critical' => true],
        'P8' => ['name' => 'Delivery Capability, Vendor & Resourcing', 'weight' => 1.2, 'critical' => false],
        'P9' => ['name' => 'Operational & Automation Readiness', 'weight' => 1.0, 'critical' => false],
    ];

    protected array $itsmDomains = [
        'D1' => ['name' => 'Service Governance & Ownership', 'weight' => 1.4, 'critical' => true],
        'D2' => ['name' => 'Incident & Major Incident Management', 'weight' => 1.4, 'critical' => true],
        'D3' => ['name' => 'Service Request Management', 'weight' => 1.0, 'critical' => false],
        'D4' => ['name' => 'Problem Management', 'weight' => 1.2, 'critical' => false],
        'D5' => ['name' => 'Change & Release Management', 'weight' => 1.3, 'critical' => true],
        'D6' => ['name' => 'Service Performance, SLA & Reporting', 'weight' => 1.2, 'critical' => false],
        'D7' => ['name' => 'Service Transition & BAU Readiness', 'weight' => 1.2, 'critical' => true],
        'D8' => ['name' => 'Service Operations & Support Model', 'weight' => 1.2, 'critical' => false],
        'D9' => ['name' => 'Supplier & Vendor Service Management', 'weight' => 1.0, 'critical' => false],
        'D10' => ['name' => 'Operational Resilience & Continuity', 'weight' => 1.3, 'critical' => true],
        'D11' => ['name' => 'Service Tooling, CMDB & Knowledge Management', 'weight' => 1.2, 'critical' => false],
        'D12' => ['name' => 'Service Intelligence & Continuous Value', 'weight' => 1.1, 'critical' => false],
    ];

    protected array $phiSnapshotQuestions = [
        'P1' => [
            'P1_S1' => 'Decision-making and escalation are clear and effective.',
            'P1_S2' => 'Programme reporting reflects the true position.',
        ],
        'P2' => [
            'P2_S1' => 'The integrated plan is realistic, credible and actively controlled.',
            'P2_S2' => 'Scope is baselined and change is governed without destabilising delivery.',
        ],
        'P3' => [
            'P3_S1' => 'Business objectives, benefits and ownership are clearly defined.',
            'P3_S2' => 'Budget, cost exposure and value delivery are visible and controlled.',
        ],
        'P4' => [
            'P4_S1' => 'Users and business leaders are prepared and engaged.',
            'P4_S2' => 'Training and adoption readiness are realistic and measurable.',
        ],
        'P5' => [
            'P5_S1' => 'Data quality and migration readiness are understood.',
            'P5_S2' => 'Data ownership and validation responsibilities are clear.',
        ],
        'P6' => [
            'P6_S1' => 'The solution and architecture fit the business and technical needs.',
            'P6_S2' => 'Process design and customisation are controlled and proportionate.',
        ],
        'P7' => [
            'P7_S1' => 'Cutover and go-live readiness are on track.',
            'P7_S2' => 'Critical dependencies and contingency planning are credible.',
        ],
        'P8' => [
            'P8_S1' => 'The programme has the right capability, vendor control and capacity to deliver.',
            'P8_S2' => 'Key delivery roles and accountabilities are covered.',
        ],
        'P9' => [
            'P9_S1' => 'BAU readiness, support and automation opportunities are understood.',
            'P9_S2' => 'Operational reporting and support handover are feasible.',
        ],
    ];

    protected array $itsmSnapshotQuestions = [
        'D1' => [
            'D1_S1' => 'Service ownership and accountability are clear.',
            'D1_S2' => 'Governance and review cadence are effective.',
        ],
        'D2' => [
            'D2_S1' => 'Incidents are managed in a controlled and timely manner.',
            'D2_S2' => 'Major incident decision-making is effective.',
        ],
        'D3' => [
            'D3_S1' => 'Requests follow a clear and efficient fulfilment path.',
            'D3_S2' => 'Request categorisation and routing are controlled.',
        ],
        'D4' => [
            'D4_S1' => 'Repeat issues are identified and addressed.',
            'D4_S2' => 'Root cause resolution is pursued beyond symptom treatment.',
        ],
        'D5' => [
            'D5_S1' => 'Change control is effective and risk-managed.',
            'D5_S2' => 'Release decisions are disciplined and supported by evidence.',
        ],
        'D6' => [
            'D6_S1' => 'Service performance and SLAs are visible.',
            'D6_S2' => 'Reporting drives action rather than passive review.',
        ],
        'D7' => [
            'D7_S1' => 'Transition into BAU is prepared and controlled.',
            'D7_S2' => 'Operational acceptance criteria are defined.',
        ],
        'D8' => [
            'D8_S1' => 'The support model is clear and workable.',
            'D8_S2' => 'Escalation and handoffs are defined.',
        ],
        'D9' => [
            'D9_S1' => 'Suppliers are governed against service obligations.',
            'D9_S2' => 'Vendor performance is visible and challenged.',
        ],
        'D10' => [
            'D10_S1' => 'Continuity and resilience controls are in place.',
            'D10_S2' => 'Operational resilience risks are known and managed.',
        ],
        'D11' => [
            'D11_S1' => 'Tooling supports efficient service delivery.',
            'D11_S2' => 'Automation is used or planned where it adds control and efficiency.',
        ],
    ];

    protected array $phiFullQuestions = [
        'P1' => [
            'P1_F1' => 'Sponsor authority is clear and active.',
            'P1_F2' => 'Steering decisions are timely and evidence-based.',
            'P1_F3' => 'Escalation routes are defined and used.',
            'P1_F4' => 'RAID governance is disciplined and current.',
            'P1_F5' => 'Programme roles and responsibilities are explicit.',
            'P1_F6' => 'Reporting reflects the true delivery position.',
            'P1_F7' => 'Governance forums drive action rather than status-only discussion.',
            'P1_F8' => 'Decision logs and action ownership are maintained.',
        ],
        'P2' => [
            'P2_F1' => 'The integrated plan is baselined and actively managed.',
            'P2_F2' => 'Critical path and dependencies are visible and controlled.',
            'P2_F3' => 'Milestones are realistic and measurable.',
            'P2_F4' => 'Forecasting and replanning are disciplined.',
            'P2_F5' => 'Risks and issues are actively managed.',
            'P2_F6' => 'Scope baseline is defined and understood.',
            'P2_F7' => 'Change requests are formally assessed and approved.',
            'P2_F8' => 'Change impact on time, cost and value is assessed before approval.',
            'P2_F9' => 'Change volume is not destabilising delivery.',
        ],
        'P3' => [
            'P3_F1' => 'Business case assumptions remain valid.',
            'P3_F2' => 'Benefits are clearly defined and owned.',
            'P3_F3' => 'KPIs for success are measurable.',
            'P3_F4' => 'Budget tracking is accurate and current.',
            'P3_F5' => 'Forecast cost at completion is understood.',
            'P3_F6' => 'Change costs are controlled and visible.',
            'P3_F7' => 'Value delivery is reviewed with the business.',
            'P3_F8' => 'Business ownership is active and accountable.',
        ],
        'P4' => [
            'P4_F1' => 'Stakeholder impacts are understood.',
            'P4_F2' => 'Change strategy is clear and realistic.',
            'P4_F3' => 'Training plans are role-based and practical.',
            'P4_F4' => 'Training attendance and readiness are measured.',
            'P4_F5' => 'Business leaders are engaged and visible.',
            'P4_F6' => 'Resistance points are known and managed.',
            'P4_F7' => 'Change champions are in place where needed.',
            'P4_F8' => 'Adoption measures exist beyond training completion.',
        ],
        'P5' => [
            'P5_F1' => 'Data migration strategy is clear.',
            'P5_F2' => 'Data ownership is assigned.',
            'P5_F3' => 'Data quality issues are visible and managed.',
            'P5_F4' => 'Data cleansing is planned and resourced.',
            'P5_F5' => 'Mock migrations are planned or performed.',
            'P5_F6' => 'Reconciliation controls are defined.',
            'P5_F7' => 'Business validation of migrated data is credible.',
            'P5_F8' => 'Migration cutover dependencies are understood.',
        ],
        'P6' => [
            'P6_F1' => 'Solution design supports the target operating model.',
            'P6_F2' => 'Process design is documented and agreed.',
            'P6_F3' => 'Customisation is controlled and justified.',
            'P6_F4' => 'Integration complexity is understood.',
            'P6_F5' => 'Architecture choices are scalable and supportable.',
            'P6_F6' => 'Design gaps are identified and owned.',
            'P6_F7' => 'Workarounds are acceptable and controlled.',
            'P6_F8' => 'Non-functional requirements are understood where relevant.',
        ],
        'P7' => [
            'P7_F1' => 'Cutover plan is detailed and credible.',
            'P7_F2' => 'Cutover roles and ownership are clear.',
            'P7_F3' => 'Cutover rehearsal strategy exists.',
            'P7_F4' => 'Go / no-go criteria are defined.',
            'P7_F5' => 'Rollback or fallback planning is credible.',
            'P7_F6' => 'Day-1 operational processes are ready.',
            'P7_F7' => 'Hypercare model is agreed.',
            'P7_F8' => 'Support escalation model is ready.',
            'P7_F9' => 'Critical business dependencies are accounted for.',
            'P7_F10' => 'Business sign-off readiness is realistic.',
        ],
        'P8' => [
            'P8_F1' => 'Programme leadership capability is sufficient.',
            'P8_F2' => 'PMO support is effective.',
            'P8_F3' => 'Internal resource capacity is realistic.',
            'P8_F4' => 'Key skill gaps are known and managed.',
            'P8_F5' => 'Vendor or SI performance is governed effectively.',
            'P8_F6' => 'Commercial accountabilities are clear.',
            'P8_F7' => 'Cross-team collaboration supports delivery.',
            'P8_F8' => 'Team stability is sufficient for the current phase.',
        ],
        'P9' => [
            'P9_F1' => 'BAU support model is defined.',
            'P9_F2' => 'Operational handover requirements are known.',
            'P9_F3' => 'Support documentation and knowledge transfer are planned.',
            'P9_F4' => 'Automation opportunities are identified and prioritised.',
            'P9_F5' => 'Operational reporting requirements are clear.',
            'P9_F6' => 'Service acceptance into BAU is credible.',
            'P9_F7' => 'Control ownership after go-live is clear.',
            'P9_F8' => 'Ongoing optimisation approach is defined.',
        ],
    ];

    protected array $itsmFullQuestions = [
        'D1' => [
            'D1_F1' => 'Service ownership is formally assigned.',
            'D1_F2' => 'Service governance forums are active and effective.',
            'D1_F3' => 'Roles and responsibilities are understood.',
            'D1_F4' => 'Governance decisions lead to measurable action.',
            'D1_F5' => 'Service risks are visible to the right stakeholders.',
            'D1_F6' => 'Ownership of service controls is clear.',
        ],
        'D2' => [
            'D2_F1' => 'Incident logging and categorisation are controlled.',
            'D2_F2' => 'Incident prioritisation is consistent.',
            'D2_F3' => 'Major incident process is defined and used.',
            'D2_F4' => 'Escalation during incidents is timely.',
            'D2_F5' => 'Restoration targets are realistic and monitored.',
            'D2_F6' => 'Incident trends are reviewed and acted upon.',
        ],
        'D3' => [
            'D3_F1' => 'Request models are defined and maintained.',
            'D3_F2' => 'Request fulfilment responsibilities are clear.',
            'D3_F3' => 'Request backlog and ageing are visible.',
            'D3_F4' => 'Standard requests are automated where appropriate.',
            'D3_F5' => 'Request quality and user experience are reviewed.',
        ],
        'D4' => [
            'D4_F1' => 'Problem backlog is visible and prioritised.',
            'D4_F2' => 'Known error handling is established.',
            'D4_F3' => 'Problem investigations address root cause.',
            'D4_F4' => 'Problem actions are tracked to closure.',
            'D4_F5' => 'Repeat incident patterns drive problem records.',
        ],
        'D5' => [
            'D5_F1' => 'Change assessment is disciplined.',
            'D5_F2' => 'Change approval authority is clear.',
            'D5_F3' => 'Emergency change handling is controlled.',
            'D5_F4' => 'Release and deployment planning is effective.',
            'D5_F5' => 'Change success/failure is reviewed.',
            'D5_F6' => 'Change risk is evaluated before implementation.',
            'D5_F7' => 'Forward schedule of change supports coordination.',
        ],
        'D6' => [
            'D6_F1' => 'SLA definitions are clear and realistic.',
            'D6_F2' => 'Performance data is accurate.',
            'D6_F3' => 'Reporting is timely and useful.',
            'D6_F4' => 'Service reviews drive corrective action.',
            'D6_F5' => 'Service KPIs reflect actual user/service outcomes.',
            'D6_F6' => 'Capacity or performance risks are visible.',
        ],
        'D7' => [
            'D7_F1' => 'Service transition planning is defined.',
            'D7_F2' => 'Operational acceptance criteria are applied.',
            'D7_F3' => 'Knowledge transfer into BAU is complete.',
            'D7_F4' => 'Support readiness is validated before handover.',
            'D7_F5' => 'Transition risks are managed.',
            'D7_F6' => 'Hypercare or early life support is planned.',
        ],
        'D8' => [
            'D8_F1' => 'Support model roles and queues are clear.',
            'D8_F2' => 'Handoffs between support levels are controlled.',
            'D8_F3' => 'Operational work instructions are available.',
            'D8_F4' => 'Escalation paths are understood.',
            'D8_F5' => 'Shift / coverage model is fit for purpose.',
            'D8_F6' => 'Operational issues are reviewed and improved.',
        ],
        'D9' => [
            'D9_F1' => 'Supplier obligations are documented.',
            'D9_F2' => 'Supplier performance is monitored.',
            'D9_F3' => 'Vendor governance meetings are effective.',
            'D9_F4' => 'Escalation with suppliers is controlled.',
            'D9_F5' => 'Third-party dependencies are understood.',
        ],
        'D10' => [
            'D10_F1' => 'Continuity plans are defined.',
            'D10_F2' => 'Recovery responsibilities are clear.',
            'D10_F3' => 'Resilience risks are assessed.',
            'D10_F4' => 'Resilience controls are tested where appropriate.',
            'D10_F5' => 'Single points of failure are understood.',
            'D10_F6' => 'Continuity arrangements align with service criticality.',
        ],
        'D11' => [
            'D11_F1' => 'Core tooling supports service management processes.',
            'D11_F2' => 'Tool configuration supports accurate control and reporting.',
            'D11_F3' => 'Automation is used to reduce manual risk and effort.',
            'D11_F4' => 'Improvement opportunities are captured and prioritised.',
            'D11_F5' => 'Tooling data quality is sufficient for reporting and control.',
        ],
    ];

    /**
     * Legacy support for existing controllers
     */
    public function getPhiQuestionsAttribute()
    {
        $data = [];
        foreach ($this->phiPillars as $code => $pillar) {
            $data[$code] = [
                'name' => $pillar['name'],
                'questions' => $this->phiSnapshotQuestions[$code] ?? []
            ];
        }
        return $data;
    }

    public function getItsmQuestionsAttribute()
    {
        $data = [];
        foreach ($this->itsmDomains as $code => $domain) {
            $data[$code] = [
                'name' => $domain['name'],
                'questions' => $this->itsmSnapshotQuestions[$code] ?? []
            ];
        }
        return $data;
    }

    public function initializeHasAssessmentQuestions()
    {
        $this->phiQuestions = $this->getPhiQuestionsAttribute();
        $this->itsmQuestions = $this->getItsmQuestionsAttribute();
    }

    // Property-like access for legacy compatibility
    protected array $phiQuestions = [];
    protected array $itsmQuestions = [];
}
