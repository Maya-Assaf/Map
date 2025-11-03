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
        ]);
        $identity_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Identity"]);
        $identity_sub->categories()->createMany([
            ["name" => "Local languages and dialects"],
            ["name" => "National and local symbols"],
            ["name" => "Traditional clothing"],
            ["name" => "Distinct customs and traditions"],
            ["name" => "Visual identity of the city"],
            ["name" => "Local literature and arts"],
        ]);
        $layers_of_the_city_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Layers of the City"]);
        $layers_of_the_city_sub->categories()->createMany([
            ["name" => "Old city"],
            ["name" => "Historical neighborhoods"],
            ["name" => "Sequential urban expansions"],
            ["name" => "Old industrial areas"],
            ["name" => "Various residential areas"],
            ["name" => "Modern urban developments"],
        ]);
        $tangible_heritage_sub = $culture_and_heritage_layer->subAspects()->create(["name" => "Tangible Heritage"]);
        $tangible_heritage_sub->categories()->createMany([
            ["name" => "Historical buildings"],
            ["name" => "Archaeological sites"],
            ["name" => "Monuments"],
            ["name" => "Museum artifacts"],
            ["name" => "Historical documents and manuscripts"],
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
            ["name" => "Property deeds"],
            ["name" => "Easement rights"],
            ["name" => "Joint ownership"],
            ["name" => "Leasing rights"],
            ["name" => "Expropriation laws for public benefit"],
            ["name" => "Land and real estate registration"],
        ]);
        $safety_standards_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Safety Standards"]);
        $safety_standards_sub->categories()->createMany([
            ["name" => "Fire alarm and protection systems"],
            ["name" => "Emergency exits"],
            ["name" => "Earthquake resistance standards"],
            ["name" => "Flood protection systems"],
            ["name" => "Electrical safety standards"],
            ["name" => "Building monitoring systems"],
        ]);
        $structural_integrity_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Structural Integrity"]);
        $structural_integrity_sub->categories()->createMany([
            ["name" => "Foundation design standards"],
            ["name" => "Structural framework requirements"],
            ["name" => "Construction material testing"],
            ["name" => "Durability and load-bearing standards"],
            ["name" => "Periodic safety inspections"],
            ["name" => "Existing building assessments"],
        ]);
        $energy_and_materials_efficiency_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Energy & Materials Efficiency"]);
        $energy_and_materials_efficiency_sub->categories()->createMany([
            ["name" => "Thermal insulation"],
            ["name" => "Efficient heating and cooling systems"],
            ["name" => "Use of sustainable building materials"],
            ["name" => "Energy consumption standards"],
            ["name" => "Renewable energy systems"],
            ["name" => "Recycling construction materials"],
        ]);
        $accessibility_and_inclusivity_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Accessibility & Inclusivity"]);
        $accessibility_and_inclusivity_sub->categories()->createMany([
            ["name" => "Ramps and elevators"],
            ["name" => "Restrooms for people with disabilities"],
            ["name" => "Braille signage and audio signals"],
            ["name" => "Wheelchair-accessible paths and spaces"],
            ["name" => "Universal design standards"],
            ["name" => "Accessible transportation systems"],
        ]);
        $health_and_sanitation_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Health & Sanitation"]);
        $health_and_sanitation_sub->categories()->createMany([
            ["name" => "Ventilation systems"],
            ["name" => "Water treatment"],
            ["name" => "Waste disposal"],
            ["name" => "Pest control"],
            ["name" => "Hygiene standards"],
            ["name" => "Sewage systems"],
        ]);
        $adaptability_and_resilience_sub = $building_code_and_policy_layer->subAspects()->create(["name" => "Adaptability & Resilience"]);
        $adaptability_and_resilience_sub->categories()->createMany([
            ["name" => "Flexible and modifiable designs"],
            ["name" => "Resistance to extreme climate conditions"],
            ["name" => "Adaptability to population changes"],
            ["name" => "Versatile usage and reuse"],
            ["name" => "Emergency response capabilities"],
            ["name" => "Sustainable construction technologies"],
        ]);
        $economic_factor_layer = Aspect::create(["name" => "Economic Factor"]);
        $international_aid_sub = $economic_factor_layer->subAspects()->create(["name" => "International Aid"]);
        $international_aid_sub->categories()->createMany([
            ["name" => "International grants and loans"],
            ["name" => "Humanitarian aid programs"],
            ["name" => "Internationally funded development projects"],
            ["name" => "International NGOs"],
            ["name" => "Economic cooperation agreements"],
            ["name" => "Reconstruction programs"],
        ]);
        $employment_development_sub = $economic_factor_layer->subAspects()->create(["name" => "Employment Development"]);
        $employment_development_sub->categories()->createMany([
            ["name" => "Vocational training programs"],
            ["name" => "Business incubators"],
            ["name" => "Employment centers"],
            ["name" => "Small enterprise support programs"],
            ["name" => "Local employment policies"],
            ["name" => "Labor market qualification programs"],
        ]);
        $economic_diversification_sub = $economic_factor_layer->subAspects()->create(["name" => "Economic Diversification"]);
        $economic_diversification_sub->categories()->createMany([
            ["name" => "Diverse economic sectors"],
            ["name" => "Manufacturing industries"],
            ["name" => "Service sector"],
            ["name" => "Digital economy"],
            ["name" => "Creative industries"],
            ["name" => "Green economy"],
        ]);
        $tourism_sub = $economic_factor_layer->subAspects()->create(["name" => "Tourism"]);
        $tourism_sub->categories()->createMany([
            ["name" => "Tourist sites"],
            ["name" => "Tourism infrastructure"],
            ["name" => "Accommodation services"],
            ["name" => "Tourism promotion"],
            ["name" => "Cultural tourism"],
            ["name" => "Eco-tourism"],
        ]);
        $financial_insecurity_sub = $economic_factor_layer->subAspects()->create(["name" => "Financial Insecurity"]);
        $financial_insecurity_sub->categories()->createMany([
            ["name" => "Social safety nets"],
            ["name" => "Poverty alleviation programs"],
            ["name" => "Social insurance"],
            ["name" => "Financial counseling services"],
            ["name" => "Economic support for families"],
            ["name" => "Microcredit systems"],
        ]);
        $public_health_layer = Aspect::create(["name" => "Public Health"]);
        $health_care_system_sub = $public_health_layer->subAspects()->create(["name" => "Health Care System"]);
        $health_care_system_sub->categories()->createMany([
            ["name" => "Hospitals"],
            ["name" => "Primary health centers"],
            ["name" => "Specialized clinics"],
            ["name" => "Emergency services"],
            ["name" => "Health insurance system"],
            ["name" => "Medical and nursing staff"],
        ]);
        $physical_health_and_disability_sub = $public_health_layer->subAspects()->create(["name" => "Physical Health & Disability"]);
        $physical_health_and_disability_sub->categories()->createMany([
            ["name" => "Rehabilitation centers"],
            ["name" => "Physical therapy services"],
            ["name" => "Assistive and prosthetic devices"],
            ["name" => "Therapeutic sports programs"],
            ["name" => "Home care services"],
            ["name" => "Disability support centers"],
        ]);
        $disease_management_sub = $public_health_layer->subAspects()->create(["name" => "Disease Management"]);
        $disease_management_sub->categories()->createMany([
            ["name" => "Infectious disease control programs"],
            ["name" => "Epidemiological monitoring systems"],
            ["name" => "Vaccination campaigns"],
            ["name" => "Chronic disease management programs"],
            ["name" => "Diagnostic laboratories"],
            ["name" => "Treatment protocols"],
        ]);
        $nutrition_sub = $public_health_layer->subAspects()->create(["name" => "Nutrition"]);
        $nutrition_sub->categories()->createMany([
            ["name" => "Nutritional awareness programs"],
            ["name" => "Nutritional counseling centers"],
            ["name" => "School nutrition programs"],
            ["name" => "Food quality monitoring"],
            ["name" => "Malnutrition prevention programs"],
            ["name" => "Food security"],
        ]);
        $medication_sub = $public_health_layer->subAspects()->create(["name" => "Medication"]);
        $medication_sub->categories()->createMany([
            ["name" => "Pharmacies"],
            ["name" => "Drug warehouses"],
        ]);
        $ug_warehouses_sub = $public_health_layer->subAspects()->create(["name" => "ug warehouses"]);
        $ug_warehouses_sub->categories()->createMany([
            ["name" => "Medicine distribution systems"],
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
            ["name" => "Mental health awareness programs"],
            ["name" => "Psychiatric emergency services"],
        ]);
        $resources_management_layer = Aspect::create(["name" => "Resources Management"]);
        $capacity_building_sub = $resources_management_layer->subAspects()->create(["name" => "Capacity Building"]);
        $capacity_building_sub->categories()->createMany([
            ["name" => "Training centers"],
            ["name" => "Skills development programs"],
            ["name" => "Knowledge and experience transfer"],
            ["name" => "Local leadership development"],
            ["name" => "Continuing education programs"],
            ["name" => "Professional collaboration networks"],
        ]);
        $water_resources_sub = $resources_management_layer->subAspects()->create(["name" => "Water Resources"]);
        $water_resources_sub->categories()->createMany([
            ["name" => "Water sources"],
            ["name" => "Water treatment plants"],
            ["name" => "Water distribution networks"],
            ["name" => "Water storage systems"],
            ["name" => "Rainwater management"],
            ["name" => "Water conservation technologies"],
        ]);
        $food_insecurity_sub = $resources_management_layer->subAspects()->create(["name" => "Food Insecurity"]);
        $food_insecurity_sub->categories()->createMany([
            ["name" => "Food banks"],
            ["name" => "Food assistance programs"],
            ["name" => "Urban farming projects"],
            ["name" => "Food distribution systems"],
            ["name" => "Hunger prevention programs"],
            ["name" => "Monitoring food prices"],
        ]);
        $material_resources_sub = $resources_management_layer->subAspects()->create(["name" => "Material Resources"]);
        $material_resources_sub->categories()->createMany([
            ["name" => "Raw material sources"],
            ["name" => "Supply chains"],
            ["name" => "Storage and distribution centers"],
            ["name" => "Material recycling"],
            ["name" => "Inventory management"],
            ["name" => "Efficient resource use technologies"],
        ]);
        $energy_resources_sub = $resources_management_layer->subAspects()->create(["name" => "Energy Resources"]);
        $energy_resources_sub->categories()->createMany([
            ["name" => "Power generation plants"],
            ["name" => "Energy transmission and distribution networks"],
            ["name" => "Renewable energy sources"],
            ["name" => "Energy storage systems"],
            ["name" => "Energy efficiency"],
            ["name" => "Energy demand management"],
        ]);
        $urban_planning_layer = Aspect::create(["name" => "Urban Planning"]);
        $public_spaces_sub = $urban_planning_layer->subAspects()->create(["name" => "Public Spaces"]);
        $public_spaces_sub->categories()->createMany([
            ["name" => "Squares and plazas"],
            ["name" => "Parks and gardens"],
            ["name" => "Public beaches"],
            ["name" => "Recreational areas"],
            ["name" => "Sidewalks and walkways"],
            ["name" => "Multi-use open spaces"],
        ]);
        $amenities_sub = $urban_planning_layer->subAspects()->create(["name" => "Amenities"]);
        $amenities_sub->categories()->createMany([
            ["name" => "Sports facilities"],
            ["name" => "Recreational amenities"],
            ["name" => "Cultural amenities"],
            ["name" => "Educational facilities"],
            ["name" => "Health facilities"],
            ["name" => "Malls and marketplaces"],
        ]);
        $housing_and_buildings_sub = $urban_planning_layer->subAspects()->create(["name" => "Housing & Buildings"]);
        $housing_and_buildings_sub->categories()->createMany([
            ["name" => "Residential units"],
            ["name" => "Commercial buildings"],
            ["name" => "Industrial buildings"],
            ["name" => "Government buildings"],
            ["name" => "Educational buildings"],
            ["name" => "Mixed-use buildings"],
        ]);
        $population_sub = $urban_planning_layer->subAspects()->create(["name" => "Population"]);
        $population_sub->categories()->createMany([
            ["name" => "Population distribution"],
            ["name" => "Population density"],
            ["name" => "Age structure"],
            ["name" => "Social and cultural diversity"],
            ["name" => "Population growth rates"],
            ["name" => "Internal migration"],
        ]);
        $land_use_sub = $urban_planning_layer->subAspects()->create(["name" => "Land Use"]);
        $land_use_sub->categories()->createMany([
            ["name" => "Residential zones"],
            ["name" => "Commercial zones"],
            ["name" => "Industrial zones"],
            ["name" => "Agricultural zones"],
            ["name" => "Recreational zones"],
            ["name" => "Protected areas"],
        ]);
        $infrastructure_sub = $urban_planning_layer->subAspects()->create(["name" => "Infrastructure"]);
        $infrastructure_sub->categories()->createMany([
            ["name" => "Water networks"],
            ["name" => "Sewage systems"],
            ["name" => "Electricity grids"],
            ["name" => "Telecommunications networks"],
            ["name" => "Waste management"],
            ["name" => "Dams and bridges"],
        ]);
        $urban_transformation_sub = $urban_planning_layer->subAspects()->create(["name" => "Urban Transformation"]);
        $urban_transformation_sub->categories()->createMany([
            ["name" => "Rehabilitation of deteriorated areas"],
            ["name" => "Urban center renewal"],
            ["name" => "Repurposing old industrial zones"],
            ["name" => "Planned urban expansion"],
            ["name" => "Urban densification"],
            ["name" => "Major urban projects"],
        ]);
        $network_and_mobility_sub = $urban_planning_layer->subAspects()->create(["name" => "Network & Mobility"]);
        $network_and_mobility_sub->categories()->createMany([
            ["name" => "Road networks"],
            ["name" => "Public transport"],
            ["name" => "Bicycle lanes"],
            ["name" => "Pedestrian paths"],
            ["name" => "Transport stations"],
            ["name" => "Intelligent transportation systems"],
        ]);
        $data_collection_and_analysis_layer = Aspect::create(["name" => "Data Collection & Analysis"]);
        $official_statistics_sub = $data_collection_and_analysis_layer->subAspects()->create(["name" => "Official Statistics"]);
        $official_statistics_sub->categories()->createMany([
            ["name" => "Population census"],
            ["name" => "Economic surveys"],
            ["name" => "Education statistics"],
            ["name" => "Health statistics"],
            ["name" => "Labor statistics"],
            ["name" => "Environmental statistics"],
        ]);
        $research_tools_sub = $data_collection_and_analysis_layer->subAspects()->create(["name" => "Research Tools"]);
        $research_tools_sub->categories()->createMany([
            ["name" => "Questionnaires"],
            ["name" => "Interviews"],
            ["name" => "Focus groups"],
            ["name" => "Monitoring and surveillance tools"],
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
            ["name" => "Community participation platforms"],
            ["name" => "Digital communication tools"],
        ]);
        $online_platforms_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Online Platforms"]);
        $online_platforms_sub->categories()->createMany([
            ["name" => "E-learning platforms"],
            ["name" => "E-commerce platforms"],
            ["name" => "E-government service platforms"],
            ["name" => "Remote work platforms"],
            ["name" => "E-health platforms"],
            ["name" => "Civic engagement platforms"],
        ]);
        $hi_technology_and_ai_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Hi-Technology & AI"]);
        $hi_technology_and_ai_sub->categories()->createMany([
            ["name" => "Artificial intelligence applications"],
            ["name" => "Internet of Things (IoT)"],
            ["name" => "Robotics"],
            ["name" => "Virtual and augmented reality"],
            ["name" => "Big data and analytics"],
            ["name" => "Smart city systems"],
        ]);
        $digital_connectivity_sub = $technology_and_digital_infrastructure_layer->subAspects()->create(["name" => "Digital Connectivity"]);
        $digital_connectivity_sub->categories()->createMany([
            ["name" => "Internet networks"],
            ["name" => "Mobile networks"],
            ["name" => "Data centers"],
            ["name" => "Public internet access points"],
            ["name" => "Fiber optic infrastructure"],
            ["name" => "Wireless network coverage"],
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
        ]);
        $waste_management_sub = $ecological_factor_layer->subAspects()->create(["name" => "Waste Management"]);
        $waste_management_sub->categories()->createMany([
            ["name" => "Waste collection centers"],
            ["name" => "Waste sorting stations"],
            ["name" => "Recycling facilities"],
            ["name" => "Organic waste treatment plants"],
            ["name" => "Sanitary landfills"],
             ["name" => "Waste reduction programs"],
        ]);
   
           
       
        $water_and_air_quality_sub = $ecological_factor_layer->subAspects()->create(["name" => "Water & Air Quality"]);
        $water_and_air_quality_sub->categories()->createMany([
            ["name" => "Air quality monitoring stations"],
            ["name" => "Water quality monitoring systems"],
            ["name" => "Air pollution treatment technologies"],
            ["name" => "Water source protection programs"],
            ["name" => "Emission reduction policies"],
            ["name" => "Low-emission zones"],
        ]);
        $climate_sub = $ecological_factor_layer->subAspects()->create(["name" => "Climate"]);
        $climate_sub->categories()->createMany([
            ["name" => "Early warning systems for climate events"],
            ["name" => "Climate change adaptation strategies"],
            ["name" => "Carbon emissions reduction initiatives"],
            ["name" => "Climate-conscious building design"],
            ["name" => "Urban heat islands"],
            ["name" => "Climate-responsive urban planning"],
        ]);
        $natural_disaster_sub = $ecological_factor_layer->subAspects()->create(["name" => "Natural Disaster"]);
        $natural_disaster_sub->categories()->createMany([
            ["name" => "Early warning systems"],
            ["name" => "Emergency response plans"],
            ["name" => "Disaster-resilient infrastructure"],
            ["name" => "Shelters"],
            ["name" => "Rescue and emergency teams"],
            ["name" => "Post-disaster recovery programs"],
        ]);
        $agriculture_sub = $ecological_factor_layer->subAspects()->create(["name" => "Agriculture"]);
        $agriculture_sub->categories()->createMany([
            ["name" => "Agricultural lands"],
            ["name" => "Irrigation systems"],
            ["name" => "Greenhouses"],
            ["name" => "Organic farming"],
            ["name" => "Seed banks"],
            ["name" => "Sustainable agriculture"],
        ]);
        $social_factor_layer = Aspect::create(["name" => "Social Factor"]);
        $civil_peace_sub = $social_factor_layer->subAspects()->create(["name" => "Civil Peace"]);
        $civil_peace_sub->categories()->createMany([
            ["name" => "Conflict resolution mechanisms"],
            ["name" => "Community peacebuilding programs"],
            ["name" => "Community dialogue initiatives"],
            ["name" => "Community justice systems"],
            ["name" => "Social cohesion programs"],
            ["name" => "Community safety nets"],
        ]);
        $immigration_sub = $social_factor_layer->subAspects()->create(["name" => "Immigration"]);
        $immigration_sub->categories()->createMany([
            ["name" => "Migrant reception services"],
            ["name" => "Integration programs"],
            ["name" => "Translation and communication services"],
            ["name" => "Legal assistance centers"],
            ["name" => "Language training programs"],
            ["name" => "Migrant support networks"],
        ]);
        $local_community_sub = $social_factor_layer->subAspects()->create(["name" => "Local Community"]);
        $local_community_sub->categories()->createMany([
            ["name" => "Local councils"],
            ["name" => "Civil society organizations"],
            ["name" => "Community initiatives"],
            ["name" => "Community support networks"],
            ["name" => "Community centers"],
            ["name" => "Community events"],
        ]);
        $adaptation_sub = $social_factor_layer->subAspects()->create(["name" => "Adaptation"]);
        $adaptation_sub->categories()->createMany([
            ["name" => "Adaptation programs to changes"],
            ["name" => "Psychosocial support services"],
            ["name" => "Community resilience-building initiatives"],
            ["name" => "Solidarity networks"],
            ["name" => "Rehabilitation programs"],
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
        ]);
        $community_engagement_sub = $social_factor_layer->subAspects()->create(["name" => "Community Engagement"]);
        $community_engagement_sub->categories()->createMany([
            ["name" => "Public participation platforms"],
            ["name" => "Volunteering programs"],
            ["name" => "Community work initiatives"],
            ["name" => "Community dialogue forums"],
            ["name" => "Public consultation mechanisms"],
            ["name" => "Community development projects"],
        ]);
    }
}
