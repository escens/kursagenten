import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    SelectControl, 
    RangeControl,
    TextControl,
    ToggleControl
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('kursagenten/kurstaxonomy', {
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title="Hovedinnstillinger">
                        <SelectControl
                            label="Velg kilde"
                            value={attributes.sourceType}
                            options={[
                                { label: 'Kurskategorier', value: 'course-category' },
                                { label: 'Kurssteder', value: 'course-location' },
                                { label: 'Instruktører', value: 'course-instructor' }
                            ]}
                            onChange={(value) => setAttributes({ sourceType: value })}
                        />

                        <SelectControl
                            label="Layouttype"
                            value={attributes.layout}
                            options={[
                                { label: 'Stablet', value: 'stablet' },
                                { label: 'Rad', value: 'rad' },
                                { label: 'Liste', value: 'liste' }
                            ]}
                            onChange={(value) => setAttributes({ layout: value })}
                        />

                        <ToggleControl
                            label="Vis som kort"
                            checked={attributes.visningstype === 'kort'}
                            onChange={(value) => setAttributes({ visningstype: value ? 'kort' : '' })}
                        />

                        <ToggleControl
                            label="Vis skygge"
                            checked={attributes.skygge}
                            onChange={(value) => setAttributes({ skygge: value })}
                        />

                        <ToggleControl
                            label="Vis beskrivelse"
                            checked={attributes.visBeskrivelse}
                            onChange={(value) => setAttributes({ visBeskrivelse: value })}
                        />
                    </PanelBody>

                    <PanelBody title="Grid-innstillinger">
                        <RangeControl
                            label="Antall kolonner (desktop)"
                            value={attributes.grid}
                            onChange={(value) => setAttributes({ grid: value })}
                            min={1}
                            max={6}
                        />
                        <RangeControl
                            label="Antall kolonner (tablet)"
                            value={attributes.gridtablet}
                            onChange={(value) => setAttributes({ gridtablet: value })}
                            min={1}
                            max={4}
                        />
                        <RangeControl
                            label="Antall kolonner (mobil)"
                            value={attributes.gridmobil}
                            onChange={(value) => setAttributes({ gridmobil: value })}
                            min={1}
                            max={2}
                        />
                    </PanelBody>

                    <PanelBody title="Bilde-innstillinger">
                        <TextControl
                            label="Bildestørrelse"
                            value={attributes.bildestr}
                            onChange={(value) => setAttributes({ bildestr: value })}
                        />
                        <SelectControl
                            label="Bildeformat"
                            value={attributes.bildeformat}
                            options={[
                                { label: '16:9', value: '16/9' },
                                { label: '4:3', value: '4/3' },
                                { label: '1:1', value: '1/1' }
                            ]}
                            onChange={(value) => setAttributes({ bildeformat: value })}
                        />
                        <TextControl
                            label="Bildeform (border-radius)"
                            value={attributes.bildeform}
                            onChange={(value) => setAttributes({ bildeform: value })}
                        />
                    </PanelBody>

                    <PanelBody title="Tekst og avstand">
                        <TextControl
                            label="Minimum fontstørrelse (px)"
                            type="number"
                            value={attributes.fontmin}
                            onChange={(value) => setAttributes({ fontmin: value })}
                        />
                        <TextControl
                            label="Maksimum fontstørrelse (px)"
                            type="number"
                            value={attributes.fontmaks}
                            onChange={(value) => setAttributes({ fontmaks: value })}
                        />
                        <TextControl
                            label="Avstand (padding)"
                            value={attributes.avstand}
                            onChange={(value) => setAttributes({ avstand: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <ServerSideRender
                    block="kursagenten/kurstaxonomy"
                    attributes={attributes}
                />
            </div>
        );
    }
}); 