<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aspect;
use App\Models\SubAspect;
use App\Models\Category;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $culture_and_heritage_layer = Aspect::create(["name" => "Culture & Heritage"]);
        $cultural_hub_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Cultural Hub"]);
        $cultural_hub_sub->categories()->createMany([
            ["name" => "Museums"],
            ["name" => "Community cultural centers"],
            ["name" => "Theaters and cinemas"],
            ["name" => "Public libraries"],
            ["name" => "Art galleries"],
            ["name" => "Traditional handicraft centers"],
            ["name" => "Temporary cultural/relief exhibition spaces"],
        ]);
        $identity_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Identity"]);
        $identity_sub->categories()->createMany([
            ["name" => "Local languages and dialects"],
            ["name" => "Monuments"],
            ["name" => "Craft centers"],
            ["name" => "Libraries/galleries"],
            ["name" => "National and local symbols"],
            ["name" => "Traditional clothing"],
            ["name" => "Distinct customs and traditions"],
            ["name" => "Visual identity of the city"],
            ["name" => "Local literature and arts"],
        ]);
        $layers_of_the_city_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Layers of the City"]);
        $layers_of_the_city_sub->categories()->createMany([
            ["name" => "Old city polygon"],
            ["name" => "Historical neighborhoods polygons"],
            ["name" => "Sequential urban expansions polygons"],
            ["name" => "Old industrial areas polygons"],
            ["name" => "Various residential areas"],
            ["name" => "Modern development polygon"],
        ]);
        $tangible_heritage_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Tangible Heritage"]);
        $tangible_heritage_sub->categories()->createMany([
            ["name" => "Historical buildings"],
            ["name" => "Archaeological sites"],
            ["name" => "Monuments"],
            ["name" => "Museum repositories"],
            ["name" => "Archive/manuscript locations"],
            ["name" => "Damaged heritage sites"],
            ["name" => "Handicrafts and traditional products"],
        ]);
        $intangible_heritage_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Intangible Heritage"]);
        $intangible_heritage_sub->categories()->createMany([
            ["name" => "Traditional music and singing"],
            ["name" => "Folk dances"],
            ["name" => "Local stories and legends"],
            ["name" => "Traditional festivals and celebrations"],
            ["name" => "Traditional culinary arts"],
            ["name" => "Traditional knowledge and practices"],
        ]);
        $building_code_and_policy_layer = Aspect::create(["name" => "Building Code & Policy"]);
        $ownership_rights_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Ownership Rights"]);
        $ownership_rights_sub->categories()->createMany([
            ["name" => "Property registry offices"],
            ["name" => "Easement rights"],
            ["name" => "Joint ownership buildings"],
            ["name" => "Leasing rights"],
            ["name" => "Expropriation laws for public benefit"],
            ["name" => "Land parcel boundaries"],
        ]);
        $safety_standards_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Safety Standards"]);
        $safety_standards_sub->categories()->createMany([
            ["name" => "Fire alarm presence"],
            ["name" => "Emergency exits locations"],
            ["name" => "Earthquake resistance standards"],
            ["name" => "Flood protection systems"],
            ["name" => "Electrical safety standards"],
            ["name" => "Building monitoring systems"],
        ]);
        $structural_integrity_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Structural Integrity"]);
        $structural_integrity_sub->categories()->createMany([
            ["name" => "Foundation design standards"],
            ["name" => "Building condition score"],
            ["name" => "Assessed/damaged building polygons"],
            ["name" => "Structural framework requirements"],
            ["name" => "Construction material testing"],
            ["name" => "Durability and load-bearing standards"],
            ["name" => "Periodic safety inspections"],
            ["name" => "Existing building assessments"],
            ["name" => "Damaged/partially standing/collapse/rubble polygons"],
        ]);
        $energy_and_materials_efficiency_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Energy & Materials Efficiency"]);
        $energy_and_materials_efficiency_sub->categories()->createMany([
            ["name" => "Buildings with insulation"],
            ["name" => "Efficient heating and cooling systems"],
            ["name" => "Use of sustainable building materials"],
            ["name" => "Energy efficient HVAC systems"],
            ["name" => "Renewable energy systems"],
            ["name" => "Recycling facilities for construction materials"],
        ]);
        $accessibility_and_inclusivity_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Accessibility & Inclusivity"]);
        $accessibility_and_inclusivity_sub->categories()->createMany([
            ["name" => "Ramps and elevators"],
            ["name" => "Accessible restrooms "],
            ["name" => "Braille signage and audio signals"],
            ["name" => "Wheelchair-accessible paths and spaces"],
            ["name" => "Universal design standards"],
            ["name" => "Accessible transport stations"],
        ]);
        $health_and_sanitation_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Health & Sanitation"]);
        $health_and_sanitation_sub->categories()->createMany([
            ["name" => "Ventilation systems"],
            ["name" => "Water treatment plants"],
            ["name" => "Waste disposal facilities "],
            ["name" => "Pest control"],
            ["name" => "Hygiene standards"],
            ["name" => "Sewage connection points"],
            ["name" => "Emergency sanitation points"],
        ]);
        $adaptability_and_resilience_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Adaptability & Resilience"]);
        $adaptability_and_resilience_sub->categories()->createMany([
            ["name" => "Flexible and modifiable designs"],
            ["name" => "Climate‑resilient buildings"],
            ["name" => "Adaptability to population changes"],
            ["name" => "Versatile usage and reuse"],
            ["name" => "Emergency shelters points"],
            ["name" => "Sustainable construction technologies"],
            ["name" => "Multi‑used/convertible facilities"],
            ["name" => "temporary shelter clusters"],
        ]);
        $economic_factor_layer = Aspect::create(["name" => "Economic Factor"]);
        $local_craft_industry_sub  = $economic_factor_layer->subAspects()->create(["name" => "Local & Craft Industry"]);
        $local_craft_industry_sub->categories()->createMany([
            ["name" => "Craft workshops"],
            ["name" => "Handicraft centers"],
            ["name" => "Artisan markets"],
            ["name" => "Traditional production clusters"],
            ["name" => "Tool/equipment workshops"],
            ["name" => "Craft training centers"],
            ["name" => "Temporary/household workshops"],
        ]);  
        $international_aid_sub = $economic_factor_layer->subAspects()->create(["name" => "International Aid"]);
        $international_aid_sub->categories()->createMany([
            ["name" => "International grants and loans"],
            ["name" => "Active international projects"],
            ["name" => "Humanitarian coordination hubs"],
            ["name" => "Internationally funded development projects"],
            ["name" => "NGO office locations"],
            ["name" => "Economic cooperation agreements"],
            ["name" => "Reconstruction programs sites"],
            ["name" => "Temporary distribution points"],
        ]);
        $employment_development_sub = $economic_factor_layer->subAspects()->create(["name" => "Employment Development"]);
        $employment_development_sub->categories()->createMany([
            ["name" => "Vocational training centers"],
            ["name" => "Business incubators"],
            ["name" => "Employment centers"],
            ["name" => "Marketplaces and market clusters"],
            ["name" => "Small enterprise support programs"],
            ["name" => "Local employment policies"],
            ["name" => "Labor market qualification programs"],
        ]);
        $economic_diversification_sub = $economic_factor_layer->subAspects()->create(["name" => "Economic Diversification"]);
        $economic_diversification_sub->categories()->createMany([
            ["name" => "Diverse economic sectors"],
            ["name" => "Industrial clusters"],
            ["name" => "Service sector"],
            ["name" => "Digital economy"],
            ["name" => "Creative industries spaces"],
            ["name" => "Green economy"],
            ["name" => "Tech hubs"],
        ]);
        $tourism_sub = $economic_factor_layer->subAspects()->create(["name" => "Tourism"]);
        $tourism_sub->categories()->createMany([
            ["name" => "Tourist sites"],
            ["name" => "Hotels"],
            ["name" => "Tourism infrastructure"],
            ["name" => "Accommodation units"],
            ["name" => "Tourism promotion"],
            ["name" => "Cultural tourism nodes"],
            ["name" => "Eco-tourism"],
            ["name" => "Visitor centers"],
            ["name" => "Temporarily closed sites"],
        ]);
        $financial_insecurity_sub = $economic_factor_layer->subAspects()->create(["name" => "Financial Insecurity"]);
        $financial_insecurity_sub->categories()->createMany([
            ["name" => "Social safety nets"],
            ["name" => "Poverty alleviation programs"],
            ["name" => "Social insurance"],
            ["name" => "Financial counseling services"],
            ["name" => "Economic support for families"],
            ["name" => "Microcredit outlets"],
            ["name" => "Social protection offices"],
            ["name" => "Cash assistance distribution points"],
        ]);
        $public_health_layer = Aspect::create(["name" => "Public Health"]);
        $health_care_system_sub = $public_health_layer->subAspects()->create(["name" => "Health Care System"]);
        $health_care_system_sub->categories()->createMany([
            ["name" => "Hospitals"],
            ["name" => "Health centers"],
            ["name" => "Clinics"],
            ["name" => "Emergency stations"],
            ["name" => "Pharmacies"],
            ["name" => "Health insurance companies"],
            ["name" => "Mobile clinics"],
            ["name" => "Medical and nursing staff"],
        ]);
        $physical_health_and_disability_sub = $public_health_layer->subAspects()->create(["name" => "Physical Health & Disability"]);
        $physical_health_and_disability_sub->categories()->createMany([
            ["name" => "Rehabilitation centers"],
            ["name" => "Physical therapy services"],
            ["name" => "Prosthetics clinics"],
            ["name" => "Mobile rehab teams"],
            ["name" => "Therapeutic sports programs"],
            ["name" => "Home care organizations"],
            ["name" => "Disability support centers"],
        ]);
        $disease_management_sub = $public_health_layer->subAspects()->create(["name" => "Disease Management"]);
        $disease_management_sub->categories()->createMany([
            ["name" => "Infectious disease control programs"],
            ["name" => "Epidemiological monitoring systems"],
            ["name" => "Vaccination centers"],
            ["name" => "Disease surveillance nodes"],
            ["name" => "Diagnostic laboratories"],
            ["name" => "Treatment protocols"],
        ]);
        $nutrition_sub = $public_health_layer->subAspects()->create(["name" => "Nutrition"]);
        $nutrition_sub->categories()->createMany([
            ["name" => "Nutritional awareness programs"],
            ["name" => "Nutritional counseling centers"],
            ["name" => "School nutrition sites"],
            ["name" => "Food distribution centers"],
            ["name" => "Emergency kitchens"],
            ["name" => "Malnutrition prevention programs"],
        ]);
        $medication_sub = $public_health_layer->subAspects()->create(["name" => "Medication"]);
        $medication_sub->categories()->createMany([
            ["name" => "Pharmacies"],
            ["name" => "Drug warehouses"],
            ["name" => "Medicine distribution points"],
            ["name" => "Pharmaceutical regulation"],
            ["name" => "Medicine support programs"],
            ["name" => "Local pharmaceutical industry"],
        ]);
        $psych_and_mental_health_sub = $public_health_layer->subAspects()->create(["name" => "Psych & Mental Health"]);
        $psych_and_mental_health_sub->categories()->createMany([
            ["name" => "Mental health centers"],
            ["name" => "Psychological counseling services"],
            ["name" => "Mental support programs"],
            ["name" => "Addiction treatment centers"],
            ["name" => "Mobile psychosocial teams"],
            ["name" => "Mental health awareness programs"],
            ["name" => "Psychiatric emergency services"],
        ]);
        $resources_management_layer = Aspect::create(["name" => "Resources Management"]);
        $capacity_building_sub = $resources_management_layer->subAspects()->create(["name" => "Capacity Building"]);
        $capacity_building_sub->categories()->createMany([
            ["name" => "Training & development centers"],
            ["name" => "Skills development programs"],
            ["name" => "Knowledge and experience transfer"],
            ["name" => "Local leadership development"],
            ["name" => "Vocational education facilities"],
            ["name" => "Professional collaboration networks"],
            ["name" => "Temporary training hubs"],
            ["name" => "Research & training hubs"],
            ["name" => "Meeting venues for capacity programs"],
        ]);
        $water_resources_sub = $resources_management_layer->subAspects()->create(["name" => "Water Resources"]);
        $water_resources_sub->categories()->createMany([
            ["name" => "Water sources"],
            ["name" => "Water treatment plants"],
            ["name" => "Water distribution nodes"],
            ["name" => "Water storage reservoirs"],
            ["name" => "Emergency water points"],
            ["name" => "Rainwater management"],
            ["name" => "Water conservation technologies"],
        ]);
        $food_insecurity_sub = $resources_management_layer->subAspects()->create(["name" => "Food Insecurity"]);
        $food_insecurity_sub->categories()->createMany([
            ["name" => "Food banks"],
            ["name" => "Food assistance distribution points"],
            ["name" => "Urban farms"],
            ["name" => "Humanitarian Food distribution hubs"],
            ["name" => "Hunger prevention programs"],
            ["name" => "Monitoring food prices"],
        ]);
        $material_resources_sub = $resources_management_layer->subAspects()->create(["name" => "Material Resources"]);
        $material_resources_sub->categories()->createMany([
            ["name" => "Raw material sources"],
            ["name" => "Supply chains"],
            ["name" => "Construction material markets"],
            ["name" => "Supply / Storage centers"],
            ["name" => "Recycling facilities"],
            ["name" => "Material distribution hubs"],
            ["name" => "Rubble staging areas"],
            ["name" => "Efficient resource use technologies"],
        ]);
        $energy_resources_sub = $resources_management_layer->subAspects()->create(["name" => "Energy Resources"]);
        $energy_resources_sub->categories()->createMany([
            ["name" => "Power generation plants"],
            ["name" => "Energy transmission and distribution nodes"],
            ["name" => "Renewable energy installations"],
            ["name" => "Energy storage facilities"],
            ["name" => "Emergency generators"],
            ["name" => "Energy efficiency"],
            ["name" => "Energy demand management"],
        ]);
        $urban_planning_layer = Aspect::create(["name" => "Urban Planning"]);
        $public_spaces_sub = $urban_planning_layer->subAspects()->create(["name" => "Public Spaces"]);
        $public_spaces_sub->categories()->createMany([
            ["name" => "Squares and plazas"],
            ["name" => "Parks and gardens"],
            ["name" => "Public beaches"],
            ["name" => "Playgrounds"],
            ["name" => "Sidewalks and walkways"],
            ["name" => "Multi-use open spaces"],
            ["name" => "Designated safe gathering points"],
        ]);
        $amenities_sub = $urban_planning_layer->subAspects()->create(["name" => "Amenities"]);
        $amenities_sub->categories()->createMany([
            ["name" => "Sports facilities"],
            ["name" => "Recreational amenities"],
            ["name" => "Cultural centers"],
            ["name" => "Educational facilities"],
            ["name" => "Health facilities"],
            ["name" => "Markets/malls"],
        ]);
        $housing_and_buildings_sub = $urban_planning_layer->subAspects()->create(["name" => "Housing & Buildings"]);
        $housing_and_buildings_sub->categories()->createMany([
            ["name" => "Residential blocks"],
            ["name" => "Commercial buildings"],
            ["name" => "Industrial sites"],
            ["name" => "Government buildings"],
            ["name" => "Educational buildings"],
            ["name" => "Mixed-use buildings"],
            ["name" => "Informal buildings"],
            ["name" => "Damaged building"],
        ]);
        $population_sub = $urban_planning_layer->subAspects()->create(["name" => "Population"]);
        $population_sub->categories()->createMany([
            ["name" => "Population density grid cells"],
            ["name" => "Population density"],
            ["name" => "Age structure"],
            ["name" => "Social and cultural diversity"],
            ["name" => "Population growth rates"],
            ["name" => "IDP /refugee camp polygons"],
            ["name" => "Informal settlement polygons"],
            ["name" => "Identified population change hotspots"],
        ]);
        $land_use_sub = $urban_planning_layer->subAspects()->create(["name" => "Land Use"]);
        $land_use_sub->categories()->createMany([
            ["name" => "Residential zones"],
            ["name" => "Commercial zones"],
            ["name" => "Industrial zones"],
            ["name" => "Agricultural zones"],
            ["name" => "Recreational zones"],
            ["name" => "Protected areas"],
            ["name" => "Reconstruction site"],
        ]);
        $infrastructure_sub = $urban_planning_layer->subAspects()->create(["name" => "Infrastructure"]);
        $infrastructure_sub->categories()->createMany([
            ["name" => "Water networks"],
            ["name" => "Sewage mains"],
            ["name" => "Electricity substations"],
            ["name" => "Telecommunications networks"],
            ["name" => "Waste transfer stations"],
            ["name" => "Dams and bridges"],
            ["name" => "Damaged infrastructure"],
        ]);
        $urban_transformation_sub = $urban_planning_layer->subAspects()->create(["name" => "Urban Transformation"]);
        $urban_transformation_sub->categories()->createMany([
            ["name" => "Rehabilitation /reconstruction sites"],
            ["name" => "Urban center renewal"],
            ["name" => "Repurposed industrial zones"],
            ["name" => "Planned expansion footprints"],
            ["name" => "Urban densification sites"],
            ["name" => "Ongoing Major projects"],
        ]);
        $network_and_mobility_sub = $urban_planning_layer->subAspects()->create(["name" => "Network & Mobility"]);
        $network_and_mobility_sub->categories()->createMany([
            ["name" => "Classified road networks "],
            ["name" => "Public transport stops"],
            ["name" => "Bicycle lanes"],
            ["name" => "Pedestrian paths"],
            ["name" => "Transport hubs"],
            ["name" => "Intelligent transportation systems"],
            ["name" => "Sidewalks and walkways"],
            ["name" => "Parking areas"],
            ["name" => "Blocked/impassable segments"],
            ["name" => "Temporary repairs/bridges"],
        ]);
        $data_collection_and_analysis_layer = Aspect::create(["name" => "Data Collection & Analysis"]);
        $official_statistics_sub = $data_collection_and_analysis_layer->subAspects()->create(["name" => "Official Statistics"]);
        $official_statistics_sub->categories()->createMany([
            ["name" => "Census centers"],
            ["name" => "Statistical tables"],
            ["name" => "Temporary IDP registration points"],
            ["name" => "Education statistics"],
            ["name" => "Health statistics"],
            ["name" => "Labor statistics"],
            ["name" => "Environmental statistics"],
        ]);
        $research_tools_sub = $data_collection_and_analysis_layer->subAspects()->create(["name" => "Research Tools"]);
        $research_tools_sub->categories()->createMany([
            ["name" => "Survey cluster points"],
            ["name" => "Interviews"],
            ["name" => "Focus groups locations"],
            ["name" => "Monitoring station locations"],
            ["name" => "Statistical data analysis"],
            ["name" => "Simulation models"],
        ]);
        $mapping_tools_sub = $data_collection_and_analysis_layer->subAspects()->create(["name" => "Mapping Tools"]);
        $mapping_tools_sub->categories()->createMany([
            ["name" => "Geographic Information Systems (GIS)"],
            ["name" => "Remote sensing"],
            ["name" => "Satellite imagery"],
            ["name" => "Participatory community mapping"],
            ["name" => "Heat maps"],
            ["name" => "Spatial data visualization"],
        ]);
        $technology_and_digital_infrastructure_layer = Aspect::create(["name" => "Technology & Digital Infrastructure"]);
        $social_networking_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Social Networking"]);
        $social_networking_sub->categories()->createMany([
            ["name" => "Social media platforms"],
            ["name" => "Virtual community groups"],
            ["name" => "Information-sharing platforms"],
            ["name" => "Professional collaboration networks"],
            ["name" => "Community digital hubs"],
            ["name" => "Public internet access points"],
        ]);
        $online_platforms_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Online Platforms"]);
        $online_platforms_sub->categories()->createMany([
            ["name" => "E-learning facilities"],
            ["name" => "E-commerce platforms"],
            ["name" => "E-government service centers"],
            ["name" => "Remote work platforms"],
            ["name" => "E-health platforms"],
            ["name" => "Civic engagement platforms"],
        ]);
        $hi_technology_and_ai_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Hi-Technology & AI"]);
        $hi_technology_and_ai_sub->categories()->createMany([
            ["name" => "Data centers"],
            ["name" => "IoT sensor clusters"],
            ["name" => "Robotics"],
            ["name" => "Virtual and augmented reality"],
            ["name" => "Big data and analytics"],
            ["name" => "Smart infrastructure control centers"],
        ]);
        $digital_connectivity_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Digital Connectivity"]);
        $digital_connectivity_sub->categories()->createMany([
            ["name" => "Internet networks"],
            ["name" => "Mobile networks towers"],
            ["name" => "Data centers"],
            ["name" => "Public Wi-Fi hotspots"],
            ["name" => "Fiber backbone node"],
            ["name" => "Wireless network coverage"],
            ["name" => "Temporary connectivity points"],
        ]);
        $ecological_factor_layer = Aspect::create(["name" => "Ecological Factor"]);
        $green_spaces_sub = $ecological_factor_layer->subAspects()->create(["name" => "Green Spaces"]);
        $green_spaces_sub->categories()->createMany([
            ["name" => "Public gardens"],
            ["name" => "Urban forests"],
            ["name" => "Green belts"],
            ["name" => "Urban agriculture"],
            ["name" => "Green roofs and walls"],
            ["name" => "Ecological corridors"],
            ["name" => "Private gardens"],
        ]);
        $waste_management_sub = $ecological_factor_layer->subAspects()->create(["name" => "Waste Management"]);
        $waste_management_sub->categories()->createMany([
            ["name" => "Waste collection centers"],
            ["name" => "Waste sorting stations"],
            ["name" => "Recycling facilities"],
            ["name" => "Organic waste treatment plants"],
            ["name" => "Landfills locations"],
            ["name" => "Emergency waste collection points"],
        ]);
   
           
       
        $water_and_air_quality_sub = $ecological_factor_layer->subAspects()->create(["name" => "Water & Air Quality"]);
        $water_and_air_quality_sub->categories()->createMany([
            ["name" => "Air quality monitoring stations"],
            ["name" => "Water quality monitoring systems"],
            ["name" => "Air pollution treatment technologies"],
            ["name" => "Water source protection programs"],
            ["name" => "Emission reduction policies"],
            ["name" => "Low-emission zones"],
            ["name" => "Emergency water testing points"],
        ]);
        $climate_sub = $ecological_factor_layer->subAspects()->create(["name" => "Climate"]);
        $climate_sub->categories()->createMany([
            ["name" => "Climate change adaptation strategies"],
            ["name" => "Early warning node locations"],
            ["name" => "Carbon emissions reduction initiatives"],
            ["name" => "Climate-conscious building design"],
            ["name" => "Urban heat islands"],
            ["name" => "Climate-responsive urban planning"],
        ]);
        $natural_disaster_sub = $ecological_factor_layer->subAspects()->create(["name" => "Natural Disaster"]);
        $natural_disaster_sub->categories()->createMany([
            ["name" => "Early warning systems nodes"],
            ["name" => "Emergency response plans"],
            ["name" => "Disaster resilient infrastructure locations"],
            ["name" => "Disaster Designated shelters"],
            ["name" => "Flood defenses"],
            ["name" => "Rescue and emergency teams"],
            ["name" => "Post-disaster recovery programs"],
        ]);
        $agriculture_sub = $ecological_factor_layer->subAspects()->create(["name" => "Agriculture"]);
        $agriculture_sub->categories()->createMany([
            ["name" => "Agricultural lands"],
            ["name" => "Irrigation systems"],
            ["name" => "Greenhouses"],
            ["name" => "Organic farming"],
            ["name" => "Seed banks locations"],
            ["name" => "Emergency seed/food depots"],

            ["name" => "Sustainable agriculture"],
        ]);
        $social_factor_layer = Aspect::create(["name" => "Social Factor"]);
        $civil_peace_sub = $social_factor_layer->subAspects()->create(["name" => "Civil Peace"]);
        $civil_peace_sub->categories()->createMany([
            ["name" => "Conflict resolution mechanisms"],
            ["name" => "Registered peace building programs offices"],
            ["name" => "Community dialogue initiatives"],
            ["name" => "Community justice systems"],
            ["name" => "Social cohesion programs"],
            ["name" => "Community safety nets"],
            ["name" => "Community mediation centers"],
            ["name" => "Police/community safety posts"],
            ["name" => "Security incident hotspots"],

        ]);
        $immigration_sub = $social_factor_layer->subAspects()->create(["name" => "Immigration"]);
        $immigration_sub->categories()->createMany([
            ["name" => "Migrant reception centers"],
            ["name" => "Integration programs"],
            ["name" => "Language/translation service locations"],
            ["name" => "Legal assistance centers"],
            ["name" => "Language training programs"],
            ["name" => "Migrant support hubs"],
            ["name" => "Entry monitoring points"],
        ]);
        $local_community_sub = $social_factor_layer->subAspects()->create(["name" => "Local Community"]);
        $local_community_sub->categories()->createMany([
            ["name" => "Local council offices"],
            ["name" => "Civil society organization premises"],
            ["name" => "Community initiatives"],
            ["name" => "Community support networks"],
            ["name" => "Community centers"],
            ["name" => "Public event venues"],
            ["name" => "Volunteer coordination hubs"],

        ]);
        $adaptation_sub = $social_factor_layer->subAspects()->create(["name" => "Adaptation"]);
        $adaptation_sub->categories()->createMany([
            ["name" => "Adaptation programs to changes"],
            ["name" => "Psychosocial support centers"],
            ["name" => "Community resilience program offices"],
            ["name" => "Solidarity networks"],
            ["name" => "Rehabilitation programs sites"],
            ["name" => "Community adaptation strategies"],
        ]);
        $education_system_sub = $social_factor_layer->subAspects()->create(["name" => "Education System"]);
        $education_system_sub->categories()->createMany([
            ["name" => "Schools"],
            ["name" => "Universities and colleges"],
            ["name" => "Vocational training centers"],
            ["name" => "Continuing education institutions"],
            ["name" => "Educational libraries"],
            ["name" => "Research centers"],
            ["name" => "Temporary learning spaces"],
        ]);
        $community_engagement_sub = $social_factor_layer->subAspects()->create(["name" => "Community Engagement"]);
        $community_engagement_sub->categories()->createMany([
            ["name" => "Participatory planning offices"],
            ["name" => "Volunteering coordination centers"],
            ["name" => "Community work initiatives"],
            ["name" => "Community dialogue forums"],
            ["name" => "Public consultation venues"],
            ["name" => "Community development projects offices"],
            ["name" => "Complaint/feedback kiosks"],
        ]);
    }
}
