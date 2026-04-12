<?php

namespace Database\Seeders;

use App\Models\Aspect;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. Public Health (PH) ---
        $public_health = Aspect::create(["name" => "Public Health"]);

        $public_health->subAspects()->create(["name" => "Health Care System"])
            ->categories()->createMany([
                ["name" => "Hospital"], ["name" => "Health Center"], ["name" => "Clinic"],
                ["name" => "Pharmacy"], ["name" => "Emergency station"]
            ]);

        $public_health->subAspects()->create(["name" => "Physical Health & Disability"])
            ->categories()->createMany([
                ["name" => "Physical therapy center"], ["name" => "Prosthetics clinic"],
                ["name" => "Disability support center"], ["name" => "Mobile rehab team"]
            ]);

        $public_health->subAspects()->create(["name" => "Disease Control"])
            ->categories()->createMany([["name" => "Vaccination center"]]);

        $public_health->subAspects()->create(["name" => "Nutrition"])
            ->categories()->createMany([["name" => "Nutritional counseling center"], ["name" => "Emergency kitchens"]]);

        $public_health->subAspects()->create(["name" => "Medication"])
            ->categories()->createMany([["name" => "Medicine distribution point"], ["name" => "Drug warehouse"]]);

        $public_health->subAspects()->create(["name" => "Psych & Mental Health"])
            ->categories()->createMany([
                ["name" => "Mental health center"], ["name" => "Psychological counseling service"],
                ["name" => "Addiction treatment center"]
            ]);

        // --- 2. Resources Management (RM) ---
        $resources_mgt = Aspect::create(["name" => "Resources Management"]);

        $resources_mgt->subAspects()->create(["name" => "Capacity Building"])
            ->categories()->createMany([
                ["name" => "Training & development center"], ["name" => "Research & training hub"],
                ["name" => "Temporary training hub"]
            ]);

        $resources_mgt->subAspects()->create(["name" => "Water Resources"])
            ->categories()->createMany([
                ["name" => "Water source"], ["name" => "Water distribution node"],
                ["name" => "Water storage reservoir"], ["name" => "Emergency water point"]
            ]);

        $resources_mgt->subAspects()->create(["name" => "Food Insecurity"])
            ->categories()->createMany([["name" => "Food bank"], ["name" => "Humanitarian Food distribution hub"]]);

        $resources_mgt->subAspects()->create(["name" => "Material Resources"])
            ->categories()->createMany([
                ["name" => "Construction material market"], ["name" => "Recycling facility"],
                ["name" => "Material distribution hub"], ["name" => "Rubble staging area"]
            ]);

        $resources_mgt->subAspects()->create(["name" => "Energy Resources"])
            ->categories()->createMany([
                ["name" => "Power generation plant"], ["name" => "Energy transmission and distribution node"],
                ["name" => "Renewable energy installation"], ["name" => "Energy storage facility"],
                ["name" => "Emergency generator"]
            ]);

        // --- 3. Urban Planning (UP) ---
        $urban_planning = Aspect::create(["name" => "Urban Planning"]);

        $urban_planning->subAspects()->create(["name" => "Public Spaces"])
            ->categories()->createMany([
                ["name" => "Squares and plaza"], ["name" => "Parks and garden"],
                ["name" => "Playground"], ["name" => "Multi-use open space"],
                ["name" => "Designated safe gathering point"]
            ]);

        $urban_planning->subAspects()->create(["name" => "Amenities"])
            ->categories()->createMany([
                ["name" => "Sports facility"], ["name" => "Religious center"],
                ["name" => "Recreational amenity"], ["name" => "Cultural center"],
                ["name" => "Health facility"], ["name" => "Market/mall"]
            ]);

        $urban_planning->subAspects()->create(["name" => "Housing & Buildings"])
            ->categories()->createMany([
                ["name" => "Residential block"], ["name" => "Commercial building"],
                ["name" => "Industrial site"], ["name" => "Government building"],
                ["name" => "Educational building"], ["name" => "Mixed-use building"],
                ["name" => "Informal building"], ["name" => "Damaged building"]
            ]);

        $urban_planning->subAspects()->create(["name" => "Population"])
            ->categories()->createMany([["name" => "Informal settlement"]]);

        $urban_planning->subAspects()->create(["name" => "Land Use"])
            ->categories()->createMany([
                ["name" => "Residential zone"], ["name" => "Commercial zone"],
                ["name" => "Industrial zone"], ["name" => "Agricultural zone"],
                ["name" => "Recreational zone"], ["name" => "Protected area"],
                ["name" => "Reconstruction site"]
            ]);

        $urban_planning->subAspects()->create(["name" => "Infrastructure"])
            ->categories()->createMany([
                ["name" => "Water network"], ["name" => "Sewage main"],
                ["name" => "Electricity substation"], ["name" => "Telecommunications network"],
                ["name" => "Waste transfer station"], ["name" => "Dams and bridge"],
                ["name" => "Damaged infrastructure"]
            ]);

        $urban_planning->subAspects()->create(["name" => "Urban Transformation"])
            ->categories()->createMany([["name" => "Rehabilitation/reconstruction site"], ["name" => "Ongoing Major project"]]);

        $urban_planning->subAspects()->create(["name" => "Network & Mobility"])
            ->categories()->createMany([
                ["name" => "Classified road network"], ["name" => "Public transport stop"],
                ["name" => "Bicycle lane"], ["name" => "Pedestrian path"],
                ["name" => "Transport hub"], ["name" => "Sidewalks and walkway"],
                ["name" => "Parking area"], ["name" => "Blocked/impassable segment"],
                ["name" => "Temporary repairs/bridges"]
            ]);

        // --- 4. Building Code (BC) ---
        $building_code = Aspect::create(["name" => "Building Code"]);

        $building_code->subAspects()->create(["name" => "Ownership Rights"])
            ->categories()->createMany([["name" => "Property registry office"], ["name" => "Land parcel boundary"], ["name" => "Leasing office"]]);

        $building_code->subAspects()->create(["name" => "Safety Standards"])
            ->categories()->createMany([["name" => "Fire alarm presence"], ["name" => "Emergency exits location"], ["name" => "Building monitoring center"]]);

        $building_code->subAspects()->create(["name" => "Structural Integrity"])
            ->categories()->createMany([["name" => "Assessed/damaged building polygon"], ["name" => "Damaged/partially standing/collapse/rubble polygon"]]);

        $building_code->subAspects()->create(["name" => "Energy & Materials Efficiency"])
            ->categories()->createMany([["name" => "Buildings with insulation"], ["name" => "Energy efficient HVAC system"], ["name" => "Recycling facilitys for construction material"]]);

        $building_code->subAspects()->create(["name" => "Accessibility & Inclusivity"])
            ->categories()->createMany([
                ["name" => "Ramps and elevator"], ["name" => "Accessible restroom"],
                ["name" => "Braille signage and audio signal"], ["name" => "Wheelchair-accessible paths and space"],
                ["name" => "Accessible transport station"]
            ]);

        $building_code->subAspects()->create(["name" => "Health & Sanitation"])
            ->categories()->createMany([
                ["name" => "Water treatment plant"], ["name" => "Waste disposal facility"],
                ["name" => "Sewage connection point"], ["name" => "Emergency sanitation point"]
            ]);

        $building_code->subAspects()->create(["name" => "Adaptability & Resilience"])
            ->categories()->createMany([
                ["name" => "Climate‑resilient building"], ["name" => "Emergency shelters point"],
                ["name" => "Multi‑used/convertible facility"], ["name" => "Temporary shelter cluster"]
            ]);

        // --- 5. Economy (EC) ---
        $economy = Aspect::create(["name" => "Economy"]);

        $economy->subAspects()->create(["name" => "Local & Craft Industry"])
            ->categories()->createMany([
                ["name" => "Craft workshop"], ["name" => "Handicraft center"], ["name" => "Artisan market"],
                ["name" => "Traditional production cluster"], ["name" => "Tool/equipment workshop"],
                ["name" => "Craft training center"], ["name" => "Temporary/household workshop"]
            ]);

        $economy->subAspects()->create(["name" => "International Aid"])
            ->categories()->createMany([
                ["name" => "Active international project"], ["name" => "Humanitarian coordination hub"],
                ["name" => "NGO office location"], ["name" => "Reconstruction programs site"],
                ["name" => "Temporary distribution point"]
            ]);

        $economy->subAspects()->create(["name" => "Employment Development"])
            ->categories()->createMany([
                ["name" => "Vocational training center"], ["name" => "Business incubator"],
                ["name" => "Employment center"], ["name" => "Marketplaces and market cluster"]
            ]);

        $economy->subAspects()->create(["name" => "Economic Diversification"])
            ->categories()->createMany([["name" => "Industrial cluster"], ["name" => "Creative industries space"], ["name" => "Tech hub"]]);

        $economy->subAspects()->create(["name" => "Tourism"])
            ->categories()->createMany([
                ["name" => "Tourist site"], ["name" => "Hotel"], ["name" => "Accommodation unit"],
                ["name" => "Cultural tourism node"], ["name" => "Visitor center"], ["name" => "Temporarily closed site"]
            ]);

        $economy->subAspects()->create(["name" => "Financial Insecurity"])
            ->categories()->createMany([["name" => "Microcredit outlet"], ["name" => "Social protection office"], ["name" => "Cash assistance distribution point"]]);

        // --- 6. Ecological Factor (EG) ---
        $ecological = Aspect::create(["name" => "Ecological Factor"]);

        $ecological->subAspects()->create(["name" => "Green Spaces"])
            ->categories()->createMany([
                ["name" => "Tree Canopy"], ["name" => "Public garden"], ["name" => "Urban forest"],
                ["name" => "Green belt"], ["name" => "Green roofs and wall"],
                ["name" => "Ecological corridor"], ["name" => "Private garden"]
            ]);

        $ecological->subAspects()->create(["name" => "Waste Management"])
            ->categories()->createMany([
                ["name" => "Waste collection point"], ["name" => "Waste sorting station"],
                ["name" => "Recycling facility"], ["name" => "Landfills location"],
                ["name" => "Emergency waste collection point"]
            ]);

        $ecological->subAspects()->create(["name" => "Water & Air Quality"])
            ->categories()->createMany([["name" => "Air quality monitoring station"], ["name" => "Water quality monitoring station"], ["name" => "Emergency water testing point"]]);

        $ecological->subAspects()->create(["name" => "Natural Disaster"])
            ->categories()->createMany([["name" => "Disaster Designated shelter"]]);

        $ecological->subAspects()->create(["name" => "Agriculture"])
            ->categories()->createMany([["name" => "Agricultural land"], ["name" => "Irrigation system"], ["name" => "Greenhouse"], ["name" => "Emergency seed/food depots"]]);

        // --- 7. Social (SO) ---
        $social = Aspect::create(["name" => "Social"]);

        $social->subAspects()->create(["name" => "Civil Peace"])
            ->categories()->createMany([["name" => "Registered peace building programs office"], ["name" => "Police/community safety post"]]);

        $social->subAspects()->create(["name" => "Immigration"])
            ->categories()->createMany([
                ["name" => "Migrant reception center"], ["name" => "Language/translation service"],
                ["name" => "Legal assistance center"], ["name" => "Migrant support hub"],
                ["name" => "Entry monitoring point"]
            ]);

        $social->subAspects()->create(["name" => "Local Community"])
            ->categories()->createMany([
                ["name" => "Local council office"], ["name" => "Community center"],
                ["name" => "Public event venue"], ["name" => "Volunteer coordination hub"]
            ]);

        $social->subAspects()->create(["name" => "Adaptation"])
            ->categories()->createMany([["name" => "Psychosocial support center"], ["name" => "Community resilience program office"], ["name" => "Rehabilitation programs site"]]);

        $social->subAspects()->create(["name" => "Education System"])
            ->categories()->createMany([
                ["name" => "School"], ["name" => "Universities and college"], ["name" => "Vocational training center"],
                ["name" => "Continuing education institution"], ["name" => "Educational library"],
                ["name" => "Research center"], ["name" => "Temporary learning space"]
            ]);

        $social->subAspects()->create(["name" => "Community Engagement"])
            ->categories()->createMany([
                ["name" => "Participatory planning office"], ["name" => "Public consultation venue"],
                ["name" => "Community development projects office"], ["name" => "Complaint/feedback kiosk"]
            ]);

        // --- 8. Technology & Digital Infrastructure (TD) ---
        $tech = Aspect::create(["name" => "Technology & Digital Infrastructure"]);

        $tech->subAspects()->create(["name" => "Social Networking"])
            ->categories()->createMany([["name" => "Community digital hub"], ["name" => "Public internet access point"]]);

        $tech->subAspects()->create(["name" => "Online Platforms"])
            ->categories()->createMany([["name" => "E-learning facility"], ["name" => "E-government service center"]]);

        $tech->subAspects()->create(["name" => "Hi-Technology & AI"])
            ->categories()->createMany([["name" => "Data center"]]);

        $tech->subAspects()->create(["name" => "Digital Connectivity"])
            ->categories()->createMany([["name" => "Mobile networks tower"], ["name" => "Public Wi-Fi hotspot"], ["name" => "Temporary connectivity points"]]);

        // --- 9. Data Collection (DC) ---
        $data_coll = Aspect::create(["name" => "Data Collection"]);
        $data_coll->subAspects()->create(["name" => "Research Tools"])
            ->categories()->createMany([["name" => "Monitoring station"]]);

        // --- 10. Culture (CU) ---
        $culture = Aspect::create(["name" => "Culture"]);

        $culture->subAspects()->create(["name" => "Cultural Hub"])
            ->categories()->createMany([
                ["name" => "Museums"], ["name" => "Community cultural center"], ["name" => "Theaters and cinema"],
                ["name" => "Public library"], ["name" => "Art gallery"], ["name" => "Traditional handicraft center"],
                ["name" => "Temporary cultural/relief exhibition space"]
            ]);

        $culture->subAspects()->create(["name" => "Identity"])
            ->categories()->createMany([["name" => "Monument"], ["name" => "Craft center"], ["name" => "Library/gallery"]]);

        $culture->subAspects()->create(["name" => "Layers of the City"])
            ->categories()->createMany([
                ["name" => "Old city polygon"], ["name" => "Historical neighborhood polygon"],
                ["name" => "Sequential urban expansion polygon"], ["name" => "Old industrial area polygon"],
                ["name" => "Modern development polygon"]
            ]);

        $culture->subAspects()->create(["name" => "Tangible Heritage"])
            ->categories()->createMany([
                ["name" => "Historical building"], ["name" => "Archaeological site"], ["name" => "Museum repository"],
                ["name" => "Archive/manuscript location"], ["name" => "Damaged heritage site"]
            ]);

        $culture->subAspects()->create(["name" => "Intangible Heritage"])
            ->categories()->createMany([["name" => "Traditional culinary site"]]);
    }
}
